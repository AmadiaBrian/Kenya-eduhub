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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #FF6B35;
            --secondary-color: #5f6368;
            --bg-color: #F0F4F9;
            --card-bg: #F0F4F9;
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

        .card-header {
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 16px;
            margin-bottom: 16px;
        }

        .form-control {
            border: 1px solid #dadce0;
            border-radius: 25px;
            padding: 10px 12px;
            font-size: 14px;
            background: var(--bg-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }

        .btn {
            padding: 10px 24px;
            border-radius: 25px;
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

        .btn-outline-secondary {
            background: white;
            color: #5f6368;
            border: 1px solid #dadce0;
            border-radius: 25px;
        }

        .btn-outline-secondary:hover {
            background: #f1f3f4;
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
            border: 2px solid #000;
            width: 100%;
            margin: 0;
            font-family: 'Times New Roman', Times, serif;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin: 0;
            padding: 0;
        }

        .table thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }

        .table th {
            border: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 12px;
            font-weight: bold;
            color: #000;
            white-space: nowrap;
        }

        .table td {
            padding: 12px;
            border: 2px solid #000;
            color: #000;
            font-size: 13px;
        }

        .table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
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

            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                width: 100%;
            }

            .table {
                font-size: 11px;
                min-width: 100%;
            }

            .table th,
            .table td {
                padding: 8px 4px;
            }

            .btn {
                padding: 10px 20px;
                font-size: 14px;
                font-weight: 500;
                border-radius: 25px;
                height: 40px;
                width: 100% !important;
            }

            .form-control {
                padding: 12px;
                font-size: 16px;
                border-radius: 25px;
                border: 1px solid #dadce0;
            }

            .form-control:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
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

        @media (max-width: 480px) {
            .card {
                padding: 12px;
            }
        }

        .material-symbols-outlined {
            font-size: 24px;
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="dashboard-page">
    <?php require_once 'includes/header.php'; ?>
    <?php $active_page = 'settings'; require_once 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
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
                <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">lock</span> Withdrawal PIN
            </div>
            <div class="card-body">
                <p class="mb-3">Create a PIN for secure withdrawals at the office. This PIN will be required when withdrawing money from your school account.</p>

                <?php if (!empty($school['withdrawal_pin'])): ?>
                    <div class="alert alert-info">
                        <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">check_circle</span> PIN is currently set. Contact admin to change your withdrawal PIN.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">warning</span> No PIN set. Please create one for secure withdrawals.
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
                            <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">save</span> Create PIN
                        </button>
                    </form>
                <?php else: ?>
                    <p class="text-muted">Your withdrawal PIN is set and active. If you need to change it, please contact the system administrator.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">image</span> School Logo
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
                    <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">upload</span> Upload Logo
                </button>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">badge</span> Admission Number Settings
            </div>
            <div class="card-body">
                <form id="settingsForm">
                    <div class="mb-3">
                        <label class="form-label">Admission Prefix</label>
                        <input type="text" class="form-control" id="admissionPrefix" placeholder="e.g., TKNP/B" value="<?php echo htmlspecialchars($school['admission_prefix'] ?? ''); ?>">
                        <small class="text-muted">This prefix will be used for all student admission numbers. Example: TKNP/B/7280</small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">save</span> Save Settings
                    </button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">menu_book</span> Subject Limits
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
                        <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">save</span> Save Subject Limits
                    </button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">email</span> Email SMTP Settings
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
                                        <span class="material-symbols-outlined" id="displayPasswordIcon">visibility</span>
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
                                <span class="material-symbols-outlined">visibility</span>
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
                        <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">save</span> Save SMTP Settings
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="testSmtpConnection()">
                        <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">science</span> Test Connection
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
                <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">sms</span> SMS Settings
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
                                <span class="material-symbols-outlined">visibility</span>
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
                                <span class="material-symbols-outlined">visibility</span>
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
                        <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">save</span> Save SMS Settings
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="checkSmsBalance()">
                        <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">account_balance_wallet</span> Check Balance
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
                <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">info</span> School Information
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
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
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

            console.log('Saving SMTP settings...', { email, smtpHost, smtpPort, encryption });

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
                console.log('SMTP save response:', data);

                if (data.success) {
                    notificationSystem.success('SMTP settings saved successfully!');
                } else {
                    notificationSystem.error(data.error || 'Failed to save SMTP settings');
                }
            } catch (error) {
                console.error('SMTP save error:', error);
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
                iconElement.textContent = 'visibility_off';
            } else {
                // Show masked password
                passwordElement.textContent = '••••••••••••••••';
                iconElement.textContent = 'visibility';
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
                const mobitechResponse = await fetch('api/check_balance.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ provider: 'mobitech' })
                });
                const mobitechData = await mobitechResponse.json();
                mobitechBalance.textContent = mobitechData.success ? mobitechData.balance + ' SMS' : 'Error: ' + (mobitechData.error || 'Unknown');
                
                // Check Text SMS balance
                const textsmsResponse = await fetch('api/check_balance.php', {
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

    <?php require_once '../includes/copywrite.php'; ?>
</body>
</html>
