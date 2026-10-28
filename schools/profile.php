<?php
// School Profile Page
// Ensure session is started and config is loaded
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
}

// Authentication check
if (!isset($_SESSION['school_id']) || !isset($_SESSION['school_token'])) {
    header('Location: index.php?route=login');
    exit;
}

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
    <meta name="theme-color" content="#FF6B35">
    <title>Profile - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #FF6B35;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
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

        .main-content {
            padding: 24px;
            padding-bottom: 80px;
            margin-top: 24px;
        }

        .page-title {
            font-size: 22px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 24px;
            text-align: center;
        }

        .card {
            background: var(--bg-color);
            border: 1px solid #e0e0e0;
            border-radius: 25px;
            padding: 24px;
            margin: 0 0 24px 0;
        }

        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
            text-align: center;
        }

        .form-control {
            border: 1px solid #dadce0;
            border-radius: 20px;
            padding: 10px 12px;
            font-size: 14px;
            background: var(--bg-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }

        .form-label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            color: var(--secondary-color);
        }

        .btn {
            padding: 10px 24px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
            width: 100%;
        }

        .btn-primary {
            background: #FF6B35;
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
            background: #e6f4ea;
            color: #137333;
            border: 1px solid rgba(19, 115, 51, 0.1);
        }

        .alert-danger {
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid rgba(197, 34, 31, 0.1);
        }

        /* Footer styling */
        .kenya-eduhub-footer-unique-2026 {
            background: var(--bg-color) !important;
        }

        /* Icon colors */
        .fa-save { color: #00AC47; }
        .fa-key { color: #0066DA; }
        .fa-upload { color: #4285F4; }

        /* Mobile */
        @media (max-width: 768px) {
            .main-content {
                padding: 0;
                padding-bottom: 80px;
                width: 100%;
            }

            .page-title {
                font-size: 18px;
                padding: 16px;
            }

            .card {
                padding: 16px;
                margin: 0 4px 16px 4px;
            }

            .btn {
                width: 100% !important;
            }

            .quick-access-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 8px !important;
            }

            .quick-access-item {
                padding: 12px !important;
            }
        }
    </style>
</head>
<body class="dashboard-page">
<?php require_once 'includes/header.php'; ?>
<?php $active_page = 'profile'; require_once 'includes/sidebar.php'; ?>

<br>
<br>
<br>
<br>

<!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">School Profile</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- School Information -->
        <div class="card">
            <h2 class="card-title">School Information</h2>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">School Name</label>
                    <input type="text" class="form-control" name="school_name" value="<?php echo htmlspecialchars($school['school_name'] ?? ''); ?>" placeholder="Enter school name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($school['email'] ?? ''); ?>" placeholder="Enter email address" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($school['phone'] ?? ''); ?>" placeholder="Enter phone number" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($school['address'] ?? ''); ?>" placeholder="Enter school address">
                </div>
                <div class="mb-3">
                    <label class="form-label">County</label>
                    <input type="text" class="form-control" name="county" value="<?php echo htmlspecialchars($school['county'] ?? ''); ?>" placeholder="Enter county">
                </div>
                <div class="mb-3">
                    <label class="form-label">School Type</label>
                    <select class="form-control" name="school_type">
                        <option value="Primary" <?php echo ($school['school_type'] ?? '') === 'Primary' ? 'selected' : ''; ?>>Primary</option>
                        <option value="Secondary" <?php echo ($school['school_type'] ?? '') === 'Secondary' ? 'selected' : ''; ?>>Secondary</option>
                        <option value="College" <?php echo ($school['school_type'] ?? '') === 'College' ? 'selected' : ''; ?>>College</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Admission Prefix</label>
                    <input type="text" class="form-control" name="admission_prefix" value="<?php echo htmlspecialchars($school['admission_prefix'] ?? ''); ?>" placeholder="e.g., TKNP/B">
                    <small class="text-muted">This prefix will be used for all student admission numbers. Example: TKNP/B/7280</small>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i> Update Profile
                </button>
            </form>
        </div>
        
        <!-- Change Password -->
        <div class="card">
            <h2 class="card-title">Change Password</h2>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input type="password" class="form-control" name="current_password" placeholder="Enter current password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" class="form-control" name="new_password" placeholder="Enter new password (min 8 characters)" required minlength="8">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" name="confirm_password" placeholder="Confirm new password" required minlength="8">
                </div>
                <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="fas fa-key me-2"></i> Change Password
                </button>
            </form>
        </div>
        
        <!-- Logo Upload -->
        <div class="card">
            <h2 class="card-title">School Logo</h2>
            <div class="mb-3">
                <label class="form-label">Current Logo</label>
                <?php if (!empty($school['logo'])): ?>
                    <div class="mb-3">
                        <img src="<?php echo htmlspecialchars($school['logo']); ?>" alt="School Logo" style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; padding: 10px;">
                    </div>
                <?php else: ?>
                    <p class="text-muted">No logo uploaded yet</p>
                <?php endif; ?>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Upload New Logo</label>
                    <input type="file" class="form-control" name="logo" accept="image/*">
                    <small class="text-muted">Accepted formats: JPG, PNG. Maximum size: 2MB</small>
                </div>
                <button type="submit" name="upload_logo" class="btn btn-primary">
                    <i class="fas fa-upload me-2"></i> Upload Logo
                </button>
            </form>
        </div>
    </main>

    <script src="../assets/js/notifications.js"></script>

<br>
<br>
<?php require_once '../includes/copywrite.php'; ?>
</body>
</html>
