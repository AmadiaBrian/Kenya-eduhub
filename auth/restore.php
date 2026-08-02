<?php
// Session is already started by the router (auth/index.php)
require_once '../includes/security_lite.php';
require_once '../config/database.php';
require_once '../includes/helpers.php';

// Check if user has restore information
if (!isset($_SESSION['deleted_account']) || !isset($_SESSION['restore_email'])) {
    // If no restore info, redirect to register with a message
    $_SESSION['restore_error'] = "No account restoration data found. Please register normally.";
    header("Location: register");
    exit();
}

$deleted_account = $_SESSION['deleted_account'];
$restore_email = $_SESSION['restore_email'];
$restore_name = $_SESSION['restore_name'] ?? '';
$restore_password = $_SESSION['restore_password'] ?? '';

$error = '';
$success = '';

// Handle restore request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_account'])) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFLite($_POST['csrf_token'])) {
        $error = "Session expired. Please refresh.";
    } else {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($password)) {
            $error = "Password is required";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match";
        } else {
            try {
                // Check if email already exists in users table
                $check_email_stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
                $check_email_stmt->bind_param("s", $restore_email);
                $check_email_stmt->execute();
                $existing_user = $check_email_stmt->get_result()->fetch_assoc();
                
                if ($existing_user) {
                    // Email already exists - user already has an account
                    unset($_SESSION['deleted_account']);
                    unset($_SESSION['restore_email']);
                    unset($_SESSION['restore_name']);
                    unset($_SESSION['restore_password']);
                    $_SESSION['restore_error'] = "This email is already registered. Please log in with your existing account.";
                    header("Location: login");
                    exit();
                }
                
                $conn->begin_transaction();
                
                // Restore user account (let database auto-generate new ID)
                $restore_user_stmt = $conn->prepare("INSERT INTO users 
                    (name, email, password, role, is_verified, verification_code, code_expires_at, created_at, last_login)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $name = $deleted_account['name'];
                $email = $deleted_account['email'];
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $role = $deleted_account['role'];
                $is_verified = $deleted_account['is_verified'];
                $verification_code = $deleted_account['verification_code'];
                $code_expires_at = $deleted_account['code_expires_at'];
                $created_at = $deleted_account['created_at'];
                $last_login = $deleted_account['last_login'];
                
                $restore_user_stmt->bind_param("ssssissss",
                    $name, $email, $password_hash, $role, $is_verified,
                    $verification_code, $code_expires_at, $created_at, $last_login
                );
                $restore_user_stmt->execute();
                
                // Get the new user ID
                $new_user_id = $conn->insert_id;
                error_log("New user ID created: $new_user_id");
                
                // Restore resources if any (update user_id to new ID)
                if (!empty($deleted_account['resources_data'])) {
                    $resources_data = json_decode($deleted_account['resources_data'], true);
                    error_log("Resources data found: " . count($resources_data) . " resources");
                    
                    if (is_array($resources_data)) {
                        foreach ($resources_data as $resource) {
                            error_log("Restoring resource: " . $resource['title']);
                            try {
                                $restore_resource_stmt = $conn->prepare("INSERT INTO resources 
                                    (user_id, title, subject, file_path, filename, file_type, description, downloads, created_at)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                
                                $restore_resource_stmt->bind_param("isssssisis",
                                    $new_user_id, $resource['title'],
                                    $resource['subject'], $resource['file_path'], $resource['filename'],
                                    $resource['file_type'], $resource['description'],
                                    $resource['downloads'], $resource['created_at']
                                );
                                $restore_resource_stmt->execute();
                                error_log("Resource restored successfully: " . $resource['title']);
                            } catch (Exception $e) {
                                error_log("Failed to restore resource " . $resource['title'] . ": " . $e->getMessage());
                            }
                        }
                    }
                } else {
                    error_log("No resources data found in deleted account");
                }
                
                // Restore user settings if any
                if (!empty($deleted_account['user_data'])) {
                    $user_data = json_decode($deleted_account['user_data'], true);
                    if (is_array($user_data)) {
                        // Create basic user settings with new user ID
                        $restore_settings_stmt = $conn->prepare("INSERT INTO user_settings 
                            (user_id, email_uploads, email_comments, email_updates, show_profile, show_email, theme, language)
                            VALUES (?, 1, 1, 1, 1, 1, 'light', 'en')");
                        $restore_settings_stmt->bind_param("i", $new_user_id);
                        $restore_settings_stmt->execute();
                    }
                }
                
                // Delete from deleted_accounts table
                $delete_deleted_stmt = $conn->prepare("DELETE FROM deleted_accounts WHERE id = ?");
                $delete_deleted_stmt->bind_param("i", $deleted_account['id']);
                $delete_deleted_stmt->execute();
                
                $conn->commit();
                
                // Clear session variables
                unset($_SESSION['deleted_account']);
                unset($_SESSION['restore_email']);
                unset($_SESSION['restore_name']);
                unset($_SESSION['restore_password']);
                
                // Redirect to login with success message
                $_SESSION['restore_success'] = "Your account has been successfully restored! Please log in.";
                header("Location: login");
                exit();
                
            } catch (Exception $e) {
                $conn->rollback();
                // Make error messages user-friendly
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $error = "This email is already registered. Please log in with your existing account.";
                } else {
                    $error = "Unable to restore account. Please try again or contact support.";
                }
                error_log("Account restoration failed: " . $e->getMessage());
            }
        }
    }
}

// Handle skip restore (continue with new registration)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['skip_restore'])) {
    // Clear session variables and redirect to registration
    unset($_SESSION['deleted_account']);
    unset($_SESSION['restore_email']);
    unset($_SESSION['restore_name']);
    unset($_SESSION['restore_password']);
    header("Location: register");
    exit();
}

$csrf_token = generateCSRFLite();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Restore Account - Kenya EduHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --gsc-blue: #1a73e8;
            --gsc-blue-hover: #1557b0;
            --gsc-gray: #5f6368;
            --gsc-light-gray: #f1f3f4;
            --gsc-border: #dadce0;
            --gsc-white: #ffffff;
            --gsc-text: #202124;
            --gsc-warning: #f29900;
            --gsc-warning-bg: #fef7e0;
            --gsc-error: #c5221f;
            --gsc-error-bg: #fce8e6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--gsc-white);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Google Sans', 'Roboto', sans-serif;
            color: var(--gsc-text);
            padding: 24px;
        }

        .restore-container {
            width: 100%;
            max-width: 560px;
        }

        .restore-header {
            margin-bottom: 32px;
            text-align: center;
        }

        .restore-logo {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
            text-decoration: none;
            font-size: 18px;
            font-weight: 500;
            color: var(--gsc-text);
            transition: all 0.2s ease;
        }

        .restore-logo:hover {
            color: #FF6B35;
        }

        .restore-logo-icon {
            width: 40px;
            height: 40px;
            background: #FFD700;
            border: 3px solid #FF6B35;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0;
        }

        .restore-logo-icon span {
            font-weight: 700;
            font-size: 20px;
        }

        .restore-logo-icon .k {
            color: #FF6B35;
            font-size: 24px;
        }

        .restore-logo-icon .e {
            color: #008000;
            font-size: 20px;
        }

        .restore-logo-text {
            font-weight: 600;
        }

        .restore-logo-text .kenya {
            color: #FF6B35;
        }

        .restore-logo-text .eduhub {
            color: #008000;
        }

        .restore-header h1 {
            font-size: 24px;
            font-weight: 400;
            color: var(--gsc-text);
            margin-bottom: 8px;
        }

        .restore-header p {
            color: var(--gsc-gray);
            font-size: 14px;
            line-height: 1.5;
        }

        .restore-card {
            background: var(--gsc-white);
            border-radius: 8px;
            padding: 24px;
        }

        .account-info {
            background: var(--gsc-warning-bg);
            border: 1px solid var(--gsc-warning);
            border-radius: 4px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .account-info h3 {
            font-size: 14px;
            font-weight: 500;
            color: var(--gsc-text);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .account-info p {
            font-size: 13px;
            color: var(--gsc-gray);
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
        }

        .account-info strong {
            color: var(--gsc-text);
            font-weight: 500;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 400;
        }

        .alert-error {
            background: var(--gsc-error-bg);
            color: var(--gsc-error);
            border: 1px solid var(--gsc-error-bg);
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: var(--gsc-text);
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
            border: 1px solid var(--gsc-border);
            border-radius: 20px;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: var(--gsc-white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--gsc-blue);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }

        .form-control::placeholder {
            color: var(--gsc-gray);
        }

        .btn {
            width: 100%;
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.2s;
            margin-bottom: 12px;
        }

        .btn-primary {
            background: #FF6B35;
            color: white;
        }

        .btn-primary:hover {
            background: #e55a2b;
        }

        .btn-secondary {
            background: var(--gsc-white);
            color: var(--gsc-blue);
            border: 1px solid var(--gsc-border);
        }

        .btn-secondary:hover {
            background: var(--gsc-light-gray);
        }

        .info-text {
            color: var(--gsc-gray);
            font-size: 12px;
            margin-top: 16px;
            text-align: center;
        }

        @media (max-width: 480px) {
            .restore-card {
                padding: 20px 16px;
            }

            .restore-header h1 {
                font-size: 20px;
            }

            body {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="restore-container">
        <div class="restore-header">
            <a href="../index.php" class="restore-logo">
                <div class="restore-logo-icon">
                    <span class="k">K</span><span class="e">E</span>
                </div>
                <span class="restore-logo-text"><span class="kenya">Kenya</span> <span class="eduhub">EduHub</span></span>
            </a>
            <h1>Restore Your Account</h1>
            <p>We found a previously deleted account with this email</p>
        </div>

        <div class="restore-card">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="account-info">
                <h3><i class="fas fa-info-circle"></i> Account Information</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($deleted_account['name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($deleted_account['email']); ?></p>
                <p><strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($deleted_account['role'])); ?></p>
                <p><strong>Deleted:</strong> <?php echo date('F j, Y', strtotime($deleted_account['deleted_at'])); ?></p>
                <?php if (!empty($deleted_account['resources_data'])): ?>
                    <p><strong>Resources:</strong> <?php echo count(json_decode($deleted_account['resources_data'], true)); ?> files</p>
                <?php endif; ?>
            </div>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Enter a new password" minlength="8">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Confirm your new password" minlength="8">
                </div>

                <button type="submit" name="restore_account" class="btn btn-primary">
                    Restore Account
                </button>

                <button type="submit" name="skip_restore" class="btn btn-secondary">
                    Create New Account Instead
                </button>
            </form>

            <p class="info-text">
                Restoring will recover all your previous data including resources and settings.
            </p>
        </div>
    </div>

    <script>
        // Password match validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>