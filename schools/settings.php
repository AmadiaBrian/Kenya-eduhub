<?php
// School Settings Page
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

// Handle PIN creation (first-time setup only - PIN changes by admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_pin') {
    $new_pin = trim($_POST['new_pin'] ?? '');
    $confirm_pin = trim($_POST['confirm_pin'] ?? '');
    
    $errors = [];
    $success = '';
    
    // Add withdrawal_pin column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE schools ADD COLUMN IF NOT EXISTS withdrawal_pin VARCHAR(255) DEFAULT NULL AFTER password");
    } catch (PDOException $e) {
        // Column might already exist, ignore error
    }
    
    // Only allow PIN creation if not already set
    if (!empty($school['withdrawal_pin'])) {
        $errors[] = 'PIN is already set. Contact admin to change your withdrawal PIN.';
    } else {
        // First time PIN creation
        if (empty($new_pin)) {
            $errors[] = 'PIN is required';
        } elseif (strlen($new_pin) < 4) {
            $errors[] = 'PIN must be at least 4 digits';
        } elseif (!ctype_digit($new_pin)) {
            $errors[] = 'PIN must contain only numbers';
        } elseif ($new_pin !== $confirm_pin) {
            $errors[] = 'PIN confirmation does not match';
        } else {
            try {
                $hashed_pin = password_hash($new_pin, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE schools SET withdrawal_pin = ? WHERE id = ?");
                $stmt->execute([$hashed_pin, $school_id]);
                $success = 'PIN created successfully!';
                // Refresh school data
                $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
                $stmt->execute([$school_id]);
                $school = $stmt->fetch();
            } catch (PDOException $e) {
                $errors[] = 'Failed to create PIN: ' . $e->getMessage();
            }
        }
    }
}

// Get current school settings
try {
    $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Failed to fetch school settings: " . $e->getMessage());
    $school = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Settings - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
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
        
        .school-avatar {
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
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
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
            background: #FF6B35;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-outline-primary {
            background: white;
            color: #FF6B35;
            border: 1px solid #FF6B35;
        }
        
        .btn-outline-primary:hover {
            background: #fff3e0;
        }
        
        .btn-success {
            background: #1e8e3e;
            color: white;
        }
        
        .btn-success:hover {
            background: #137333;
        }
        
        .btn-danger {
            background: #d93025;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b92b20;
        }
        
        .table {
            border-collapse: collapse;
            background: white;
            border: 1px solid #000;
            width: 100%;
            margin: 0;
        }
        
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        
        .table thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }
        
        .table th {
            border: 1px solid #000;
            border-bottom: 2px solid #000;
            padding: 12px;
            font-weight: 600;
            color: #000;
            font-size: 13px;
            text-transform: uppercase;
        }
        
        .table td {
            padding: 12px;
            border: 1px solid #000;
            color: #000;
            font-size: 13px;
        }
        
        .table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .table tbody tr:hover {
            background: #f0f0f0;
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
            html, body {
                overflow-x: hidden;
                position: relative;
            }
            
            .header {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                padding: 0 16px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.08);
                transform: none !important;
            }
            
            .logo span {
                font-size: 18px;
            }
            
            .menu-btn {
                padding: 8px;
                border-radius: 50%;
                transition: background 0.2s;
            }
            
            .menu-btn:hover {
                background: rgba(0,0,0,0.04);
            }
            
            .sidebar {
                position: fixed !important;
                transform: translateX(-256px);
                box-shadow: 2px 0 8px rgba(0,0,0,0.15);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            
            .page-title {
                font-size: 22px;
                font-weight: 400;
                margin-bottom: 16px;
            }
            
            .card {
                padding: 16px;
                margin-bottom: 16px;
                border-radius: 12px;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border-radius: 8px;
                width: 100%;
            }
            
            .table {
                min-width: 600px;
                width: 100%;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 14px;
                font-weight: 500;
                border-radius: 8px;
                height: 40px;
            }
            
            .form-control {
                padding: 12px;
                font-size: 16px;
                border-radius: 8px;
                border: 1px solid #dadce0;
            }
            
            .form-control:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
            }
            
            .card-header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
                padding-bottom: 12px;
                border-bottom: 1px solid #e8eaed;
            }
            
            .card-header .btn {
                width: 100%;
            }
            
            .card {
                text-align: center;
            }
            
            .card-header {
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 0 12px;
            }
            
            .logo span {
                font-size: 16px;
            }
            
            .main-content {
                padding: 12px;
            }
            
            .page-title {
                font-size: 18px;
                margin-bottom: 16px;
            }
            
            .card {
                padding: 12px;
            }
            
            .table {
                min-width: 100%;
            }
            
            .menu-btn {
                padding: 8px;
            }
            
            .school-avatar {
                width: 28px;
                height: 28px;
                font-size: 12px;
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
            <div class="school-avatar">
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
                <i class="fas fa-user-graduate"></i> Students
            </a>
            <a class="nav-link" href="teachers">
                <i class="fas fa-chalkboard-teacher"></i> Teachers
            </a>
            <a class="nav-link" href="classes">
                <i class="fas fa-chalkboard"></i> Classes
            </a>
            <a class="nav-link" href="streams">
                <i class="fas fa-layer-group"></i> Streams
            </a>
            <a class="nav-link" href="subjects">
                <i class="fas fa-book"></i> Subjects
            </a>
            <a class="nav-link" href="exam-types">
                <i class="fas fa-clipboard-list"></i> Exam Types
            </a>
            <a class="nav-link" href="timetable">
                <i class="fas fa-calendar-alt"></i> Timetable
            </a>
            <a class="nav-link" href="grading">
                <i class="fas fa-chart-bar"></i> Grading
            </a>
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="results">
                <i class="fas fa-clipboard-list"></i> Results
            </a>
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="calendar">
                <i class="fas fa-calendar"></i> Calendar
            </a>
            <a class="nav-link" href="fees">
                <i class="fas fa-money-bill-wave"></i> Fees
            </a>
            <a class="nav-link" href="invoices">
                <i class="fas fa-file-invoice-dollar"></i> Invoices
            </a>
            <a class="nav-link" href="finance-managers">
                <i class="fas fa-user-tie"></i> Finance Managers
            </a>
            <a class="nav-link" href="account">
                <i class="fas fa-wallet"></i> Account Balance
            </a>
            <a class="nav-link" href="parents">
                <i class="fas fa-users"></i> Parents
            </a>
            <a class="nav-link" href="disciplinary">
                <i class="fas fa-shield-alt"></i> Disciplinary
            </a>
            <a class="nav-link" href="disciplinary-action-types">
                <i class="fas fa-list-alt"></i> Disciplinary Types
            </a>
            <a class="nav-link" href="librarians">
                <i class="fas fa-book-reader"></i> Librarians
            </a>
            <a class="nav-link" href="duty-assignments">
                <i class="fas fa-clipboard-list"></i> Duty Assignments
            </a>
            <a class="nav-link" href="examination-heads">
                <i class="fas fa-user-tie"></i> Examination Heads
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Settings</div>
            <a class="nav-link active" href="settings">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a class="nav-link" href="profile">
                <i class="fas fa-user"></i> Profile
            </a>
            <a class="nav-link" href="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">School Settings</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-lock me-2"></i> Withdrawal PIN
            </div>
            <div class="card-body">
                <p class="mb-3">Create a PIN for secure withdrawals at the office. This PIN will be required when withdrawing money from your school account.</p>
                
                <?php if (!empty($school['withdrawal_pin'])): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-check-circle me-2"></i> PIN is currently set. Contact admin to change your withdrawal PIN.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i> No PIN set. Please create one for secure withdrawals.
                    </div>
                <?php endif; ?>
                
                <?php if (empty($school['withdrawal_pin'])): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_pin">
                        
                        <div class="mb-3">
                            <label class="form-label">New PIN</label>
                            <input type="password" class="form-control" name="new_pin" placeholder="Enter new PIN (4+ digits)" required minlength="4" pattern="[0-9]+" title="PIN must be at least 4 digits and contain only numbers">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Confirm PIN</label>
                            <input type="password" class="form-control" name="confirm_pin" placeholder="Confirm new PIN" required minlength="4" pattern="[0-9]+" title="PIN must be at least 4 digits and contain only numbers">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Create PIN
                        </button>
                    </form>
                <?php else: ?>
                    <p class="text-muted">Your withdrawal PIN is set and active. If you need to change it, please contact the system administrator.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-image me-2"></i> School Logo
            </div>
            <div class="card-body">
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
                <div class="mb-3">
                    <label class="form-label">Upload New Logo</label>
                    <input type="file" class="form-control" id="logoUpload" accept="image/*">
                    <small class="text-muted">Accepted formats: JPG, PNG, GIF. Maximum size: 2MB</small>
                </div>
                <button type="button" class="btn btn-primary" onclick="uploadLogo()">
                    <i class="fas fa-upload me-2"></i> Upload Logo
                </button>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-id-card me-2"></i> Admission Number Settings
            </div>
            <div class="card-body">
                <form id="settingsForm">
                    <div class="mb-3">
                        <label class="form-label">Admission Prefix</label>
                        <input type="text" class="form-control" id="admissionPrefix" placeholder="e.g., TKNP/B" value="<?php echo htmlspecialchars($school['admission_prefix'] ?? ''); ?>">
                        <small class="text-muted">This prefix will be used for all student admission numbers. Example: TKNP/B/7280</small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-book me-2"></i> Subject Limits
            </div>
            <div class="card-body">
                <form id="subjectLimitsForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Minimum Subjects</label>
                            <input type="number" class="form-control" id="minSubjects" placeholder="e.g., 7" value="<?php echo htmlspecialchars($school['min_subjects'] ?? 7); ?>" min="1" required>
                            <small class="text-muted">Minimum number of subjects a student must take for grading</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Maximum Subjects</label>
                            <input type="number" class="form-control" id="maxSubjects" placeholder="e.g., 8" value="<?php echo htmlspecialchars($school['max_subjects'] ?? 8); ?>" min="1" required>
                            <small class="text-muted">Maximum number of subjects a student can take</small>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Save Subject Limits
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-envelope me-2"></i> Email SMTP Settings
            </div>
            <div class="card-body">
                <!-- Current Settings Display -->
                <div id="currentSmtpSettings" class="mb-4" style="display: none;">
                    <h5 class="mb-3">Current SMTP Settings</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td id="displayEmail">-</td>
                            </tr>
                            <tr>
                                <td><strong>App Password:</strong></td>
                                <td>
                                    <span id="displayPassword">••••••••••••••••</span>
                                    <button type="button" class="btn btn-sm btn-link" onclick="toggleDisplayPassword()" style="padding: 0; margin-left: 10px;">
                                        <i class="fas fa-eye" id="displayPasswordIcon"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>SMTP Host:</strong></td>
                                <td id="displayHost">-</td>
                            </tr>
                            <tr>
                                <td><strong>SMTP Port:</strong></td>
                                <td id="displayPort">-</td>
                            </tr>
                            <tr>
                                <td><strong>Encryption:</strong></td>
                                <td id="displayEncryption">-</td>
                            </tr>
                            <tr>
                                <td><strong>Last Updated:</strong></td>
                                <td id="displayUpdated">-</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Settings Form -->
                <form id="smtpSettingsForm">
                    <div class="mb-3">
                        <label class="form-label">Gmail Email Address</label>
                        <input type="email" class="form-control" id="smtpEmail" placeholder="your-school@gmail.com" required>
                        <small class="text-muted">Use your school's Gmail address for sending results via PHPMailer</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gmail App Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="smtpPassword" placeholder="Enter your Gmail App Password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('smtpPassword', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Generate an App Password from your Google Account Security settings</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" id="smtpHost" value="smtp.gmail.com" readonly>
                        <small class="text-muted">Gmail SMTP server (default: smtp.gmail.com)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SMTP Port</label>
                        <input type="number" class="form-control" id="smtpPort" value="587" readonly>
                        <small class="text-muted">Gmail SMTP port (default: 587 for TLS)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Encryption</label>
                        <input type="text" class="form-control" id="smtpEncryption" value="tls" readonly>
                        <small class="text-muted">Encryption method (default: TLS)</small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Save SMTP Settings
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="testSmtpConnection()">
                        <i class="fas fa-vial me-2"></i> Test Connection
                    </button>
                </form>
                
                <!-- Terminal Output Section -->
                <div class="mt-4">
                    <h5 class="mb-2">SMTP Connection Test Output</h5>
                    <div id="terminal" class="terminal">
                        <div class="terminal-line info" id="terminalPath">Ready to test SMTP connection...</div>
                        <div class="terminal-line info">Enter email and app password above, then click "Test Connection"</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-sms me-2"></i> SMS Settings
            </div>
            <div class="card-body">
                <form id="smsSettingsForm">
                    <div class="mb-3">
                        <label class="form-label">SMS Provider</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card p-3" style="border: 2px solid #e0e0e0; cursor: pointer;" id="mobitechCard" onclick="selectSmsProvider('mobitech')">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="sms_provider" value="mobitech" id="mobitechProvider" <?= ($school['sms_provider'] ?? 'mobitech') === 'mobitech' ? 'checked' : '' ?>>
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
                                        <input class="form-check-input" type="radio" name="sms_provider" value="textsms" id="textsmsProvider" <?= ($school['sms_provider'] ?? 'mobitech') === 'textsms' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="textsmsProvider">
                                            <strong>Text SMS</strong>
                                            <br><small class="text-muted">Alternative SMS provider</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="mobitechApiKeyField">
                        <label class="form-label">Mobitech API Key</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="mobitechApiKey" placeholder="Enter your Mobitech API key" value="<?= htmlspecialchars($school['mobitech_api_key'] ?? '') ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('mobitechApiKey', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Get your API key from <a href="https://mobitech.co.ke" target="_blank">Mobitech</a></small>
                    </div>
                    
                    <div class="mb-3" id="mobitechSenderIdField">
                        <label class="form-label">Mobitech Sender ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="mobitechSenderId" placeholder="Enter your Mobitech sender ID" maxlength="11" value="<?= htmlspecialchars($school['mobitech_sender_id'] ?? '') ?>">
                        <small class="text-muted">Your unique sender ID for Mobitech (max 11 characters)</small>
                    </div>
                    
                    <div class="mb-3" id="textsmsApiKeyField" style="display: <?= ($school['sms_provider'] ?? 'mobitech') === 'textsms' ? 'block' : 'none' ?>;">
                        <label class="form-label">Text SMS API Key</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="textsmsApiKey" placeholder="Enter your Text SMS API key" value="<?= htmlspecialchars($school['textsms_api_key'] ?? '') ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('textsmsApiKey', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Get your API key from <a href="https://textsms.co.ke" target="_blank">Text SMS</a></small>
                    </div>
                    
                    <div class="mb-3" id="textsmsPartnerIdField" style="display: <?= ($school['sms_provider'] ?? 'mobitech') === 'textsms' ? 'block' : 'none' ?>;">
                        <label class="form-label">Text SMS Partner ID</label>
                        <input type="text" class="form-control" id="textsmsPartnerId" placeholder="Enter your Partner ID" value="<?= htmlspecialchars($school['textsms_partner_id'] ?? '') ?>">
                        <small class="text-muted">Your Partner ID from Text SMS account</small>
                    </div>
                    
                    <div class="mb-3" id="textsmsSenderIdField" style="display: <?= ($school['sms_provider'] ?? 'mobitech') === 'textsms' ? 'block' : 'none' ?>;">
                        <label class="form-label">Text SMS Sender ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="textsmsSenderId" placeholder="Enter your Text SMS sender ID" maxlength="11" value="<?= htmlspecialchars($school['textsms_sender_id'] ?? '') ?>">
                        <small class="text-muted">Your unique sender ID for Text SMS (max 11 characters)</small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="smsEnabled" <?= ($school['sms_enabled'] ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="smsEnabled">
                                <strong>Enable SMS Notifications</strong>
                            </label>
                        </div>
                        <small class="text-muted">Turn on to send SMS notifications to parents and students</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Save SMS Settings
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="checkSmsBalance()">
                        <i class="fas fa-wallet me-2"></i> Check Balance
                    </button>
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
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle me-2"></i> School Information
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <td><strong>School Code:</strong></td>
                            <td><?php echo htmlspecialchars($school['school_code'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <td><strong>School Name:</strong></td>
                            <td><?php echo htmlspecialchars($school['school_name'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td><?php echo htmlspecialchars($school['email'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <td><strong>School Type:</strong></td>
                            <td><?php echo htmlspecialchars($school['school_type'] ?? ''); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
        
        // Load current settings
        document.addEventListener('DOMContentLoaded', function() {
            loadSmtpSettings();
            
            // Set initial terminal message
            const terminalPath = document.getElementById('terminalPath');
            if (terminalPath) {
                terminalPath.textContent = 'kenyaeduhub@smtp-test> Ready to test SMTP connection...';
            }
        });
        
        // Toggle password visibility
        function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Upload logo
        async function uploadLogo() {
            const fileInput = document.getElementById('logoUpload');
            const file = fileInput.files[0];
            
            if (!file) {
                alert('Please select a file to upload');
                return;
            }
            
            // Validate file type
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file');
                return;
            }
            
            // Validate file size (2MB max)
            if (file.size > 2 * 1024 * 1024) {
                alert('File size must be less than 2MB');
                return;
            }
            
            const formData = new FormData();
            formData.append('logo', file);
            
            try {
                const response = await fetch('api/settings.php?type=logo', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Logo uploaded successfully!');
                    location.reload();
                } else {
                    alert(data.error || 'Failed to upload logo');
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            }
        }
        
        // Save subject limits
        document.getElementById('subjectLimitsForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const minSubjects = document.getElementById('minSubjects').value;
            const maxSubjects = document.getElementById('maxSubjects').value;

            if (parseInt(minSubjects) > parseInt(maxSubjects)) {
                alert('Minimum subjects cannot be greater than maximum subjects');
                return;
            }

            try {
                const response = await fetch('api/settings.php?type=subject_limits', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        min_subjects: minSubjects,
                        max_subjects: maxSubjects
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Subject limits saved successfully!');
                } else {
                    alert(data.error || 'Failed to save subject limits');
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            }
        });

        // Save settings
        document.getElementById('settingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const admissionPrefix = document.getElementById('admissionPrefix').value;
            
            try {
                const response = await fetch('api/settings.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        admission_prefix: admissionPrefix
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    notificationSystem.success('Settings saved successfully!');
                } else {
                    notificationSystem.error(data.error || 'Failed to save settings');
                }
            } catch (error) {
                notificationSystem.error('Error', 'An error occurred. Please try again.');
            }
        });
        
        // Load SMTP settings
        async function loadSmtpSettings() {
            try {
                const response = await fetch('api/smtp_settings.php');
                const data = await response.json();
                
                if (data.success && data.data) {
                    // Populate form fields
                    document.getElementById('smtpEmail').value = data.data.email || '';
                    document.getElementById('smtpPassword').value = data.data.app_password || '';
                    document.getElementById('smtpHost').value = data.data.smtp_host || 'smtp.gmail.com';
                    document.getElementById('smtpPort').value = data.data.smtp_port || 587;
                    document.getElementById('smtpEncryption').value = data.data.encryption || 'tls';
                    
                    // Populate display table
                    document.getElementById('displayEmail').textContent = data.data.email || '-';
                    document.getElementById('displayPassword').textContent = '••••••••••••••••';
                    document.getElementById('displayPassword').dataset.password = data.data.app_password || '';
                    document.getElementById('displayHost').textContent = data.data.smtp_host || '-';
                    document.getElementById('displayPort').textContent = data.data.smtp_port || '-';
                    document.getElementById('displayEncryption').textContent = data.data.encryption || '-';
                    
                    if (data.data.updated_at) {
                        const updatedDate = new Date(data.data.updated_at);
                        document.getElementById('displayUpdated').textContent = updatedDate.toLocaleString();
                    } else {
                        document.getElementById('displayUpdated').textContent = '-';
                    }
                    
                    // Show current settings display
                    document.getElementById('currentSmtpSettings').style.display = 'block';
                }
            } catch (error) {
                console.error('Error loading SMTP settings:', error);
            }
        }
        
        // Save SMTP settings
        document.getElementById('smtpSettingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('smtpEmail').value;
            const appPassword = document.getElementById('smtpPassword').value;
            const smtpHost = document.getElementById('smtpHost').value;
            const smtpPort = document.getElementById('smtpPort').value;
            const encryption = document.getElementById('smtpEncryption').value;
            
            try {
                const response = await fetch('api/smtp_settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        email: email,
                        app_password: appPassword,
                        smtp_host: smtpHost,
                        smtp_port: smtpPort,
                        encryption: encryption
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    notificationSystem.success('SMTP settings saved successfully!');
                } else {
                    notificationSystem.error(data.error || 'Failed to save SMTP settings');
                }
            } catch (error) {
                notificationSystem.error('Error', 'An error occurred. Please try again.');
            }
        });
        
        // Test SMTP connection
        async function testSmtpConnection() {
            const terminal = document.getElementById('terminal');
            const email = document.getElementById('smtpEmail').value;
            const appPassword = document.getElementById('smtpPassword').value;
            
            // Clear terminal and add initial message
            terminal.innerHTML = '';
            addTerminalLine('kenyaeduhub@smtp-test> Starting SMTP connection test...', 'info');
            addTerminalLine('kenyaeduhub@smtp-test> Email: ' + email, 'info');
            addTerminalLine('kenyaeduhub@smtp-test> Password length: ' + appPassword.length + ' characters', 'info');
            
            if (!email || !appPassword) {
                addTerminalLine('kenyaeduhub@smtp-test> ERROR: Missing email or app password', 'error');
                addTerminalLine('kenyaeduhub@smtp-test> Please enter both fields and try again', 'warning');
                return;
            }
            
            try {
                addTerminalLine('kenyaeduhub@smtp-test> Sending test request to API...', 'info');
                
                const response = await fetch('api/smtp_settings.php?action=test', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        email: email,
                        app_password: appPassword
                    })
                });
                
                addTerminalLine('kenyaeduhub@smtp-test> Response received (Status: ' + response.status + ')', 'info');
                
                const data = await response.json();
                
                if (data.success) {
                    addTerminalLine('kenyaeduhub@smtp-test> SUCCESS: SMTP connection test successful!', 'success');
                    addTerminalLine('kenyaeduhub@smtp-test> Gmail SMTP server is accessible and credentials are valid', 'success');
                } else {
                    addTerminalLine('kenyaeduhub@smtp-test> ERROR: SMTP connection test failed', 'error');
                    addTerminalLine('kenyaeduhub@smtp-test> Error details: ' + (data.error || 'Unknown error'), 'error');
                    addTerminalLine('kenyaeduhub@smtp-test> Please check your email and app password', 'warning');
                }
            } catch (error) {
                addTerminalLine('kenyaeduhub@smtp-test> ERROR: Request failed', 'error');
                addTerminalLine('kenyaeduhub@smtp-test> Error details: ' + error.message, 'error');
                addTerminalLine('kenyaeduhub@smtp-test> Please check your network connection', 'warning');
            }
            
            addTerminalLine('kenyaeduhub@smtp-test> Test completed', 'info');
        }
        
        // Helper function to add lines to terminal
        function addTerminalLine(text, type = 'info') {
            const terminal = document.getElementById('terminal');
            const line = document.createElement('div');
            line.className = 'terminal-line ' + type;
            line.textContent = text;
            terminal.appendChild(line);
            terminal.scrollTop = terminal.scrollHeight; // Auto-scroll to bottom
        }
        
        // Toggle display password visibility
        function toggleDisplayPassword() {
            const passwordElement = document.getElementById('displayPassword');
            const iconElement = document.getElementById('displayPasswordIcon');
            const actualPassword = passwordElement.dataset.password;
            
            if (passwordElement.textContent === '••••••••••••••••') {
                // Show actual password
                passwordElement.textContent = actualPassword;
                iconElement.classList.remove('fa-eye');
                iconElement.classList.add('fa-eye-slash');
            } else {
                // Show masked password
                passwordElement.textContent = '••••••••••••••••';
                iconElement.classList.remove('fa-eye-slash');
                iconElement.classList.add('fa-eye');
            }
        }
        
        // Select SMS provider
        function selectSmsProvider(provider) {
            const mobitechCard = document.getElementById('mobitechCard');
            const textsmsCard = document.getElementById('textsmsCard');
            const mobitechProvider = document.getElementById('mobitechProvider');
            const textsmsProvider = document.getElementById('textsmsProvider');
            const mobitechField = document.getElementById('mobitechApiKeyField');
            const mobitechSenderIdField = document.getElementById('mobitechSenderIdField');
            const textsmsField = document.getElementById('textsmsApiKeyField');
            const textsmsPartnerIdField = document.getElementById('textsmsPartnerIdField');
            const textsmsSenderIdField = document.getElementById('textsmsSenderIdField');
            
            if (provider === 'mobitech') {
                mobitechCard.style.borderColor = '#FF6B35';
                textsmsCard.style.borderColor = '#e0e0e0';
                mobitechProvider.checked = true;
                mobitechField.style.display = 'block';
                mobitechSenderIdField.style.display = 'block';
                textsmsField.style.display = 'none';
                textsmsPartnerIdField.style.display = 'none';
                textsmsSenderIdField.style.display = 'none';
            } else {
                textsmsCard.style.borderColor = '#FF6B35';
                mobitechCard.style.borderColor = '#e0e0e0';
                textsmsProvider.checked = true;
                textsmsField.style.display = 'block';
                textsmsPartnerIdField.style.display = 'block';
                textsmsSenderIdField.style.display = 'block';
                mobitechField.style.display = 'none';
                mobitechSenderIdField.style.display = 'none';
            }
        }
        
        // Save SMS settings
        document.getElementById('smsSettingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const smsProvider = document.querySelector('input[name="sms_provider"]:checked').value;
            const mobitechApiKey = document.getElementById('mobitechApiKey').value;
            const mobitechSenderId = document.getElementById('mobitechSenderId').value;
            const textsmsApiKey = document.getElementById('textsmsApiKey').value;
            const textsmsPartnerId = document.getElementById('textsmsPartnerId').value;
            const textsmsSenderId = document.getElementById('textsmsSenderId').value;
            const smsEnabled = document.getElementById('smsEnabled').checked ? 1 : 0;
            
            try {
                const response = await fetch('api/settings.php?type=sms_settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        sms_provider: smsProvider,
                        mobitech_api_key: mobitechApiKey,
                        mobitech_sender_id: mobitechSenderId,
                        textsms_api_key: textsmsApiKey,
                        textsms_partner_id: textsmsPartnerId,
                        textsms_sender_id: textsmsSenderId,
                        sms_enabled: smsEnabled
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    notificationSystem.success('SMS settings saved successfully!');
                } else {
                    notificationSystem.error(data.error || 'Failed to save SMS settings');
                }
            } catch (error) {
                notificationSystem.error('Error', 'An error occurred. Please try again.');
            }
        });
        
        // Initialize SMS provider selection
        document.addEventListener('DOMContentLoaded', function() {
            const currentProvider = document.querySelector('input[name="sms_provider"]:checked')?.value || 'mobitech';
            selectSmsProvider(currentProvider);
        });
        
        // Check SMS balance
        async function checkSmsBalance() {
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
                const mobitechResponse = await fetch('../sms/api/check_balance.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ provider: 'mobitech' })
                });
                const mobitechData = await mobitechResponse.json();
                mobitechBalance.textContent = mobitechData.success ? mobitechData.balance + ' SMS' : 'Error: ' + (mobitechData.error || 'Unknown');
                
                // Check Text SMS balance
                const textsmsResponse = await fetch('../sms/api/check_balance.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ provider: 'textsms' })
                });
                const textsmsData = await textsmsResponse.json();
                textsmsBalance.textContent = textsmsData.success ? textsmsData.balance + ' SMS' : 'Error: ' + (textsmsData.error || 'Unknown');
                
                // Update last checked time
                balanceLastChecked.textContent = new Date().toLocaleString();
                
                notificationSystem.success('SMS balance checked successfully!');
            } catch (error) {
                mobitechBalance.textContent = 'Error';
                textsmsBalance.textContent = 'Error';
                notificationSystem.error('Failed to check SMS balance');
            }
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
