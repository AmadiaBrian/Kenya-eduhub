<?php
// School Settings Page
// Authentication is handled by index.php router
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';

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
            <a class="nav-link" href="parents">
                <i class="fas fa-users"></i> Parents
            </a>
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="fees">
                <i class="fas fa-money-bill-wave"></i> Fees
            </a>
            <a class="nav-link" href="invoices">
                <i class="fas fa-file-invoice-dollar"></i> Invoices
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Settings</div>
            <a class="nav-link active" href="settings">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a class="nav-link" href="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">School Settings</h1>
        
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
