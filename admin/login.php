<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include security first
require_once '../includes/security_lite.php';

require_once '../config.php';

// Security: Rate limiting for admin login
$admin_login_identifier = $_SERVER['REMOTE_ADDR'] . '_admin_login';
if (!checkRateLimit($admin_login_identifier, 5, 900)) { // 5 attempts per 15 minutes (stricter for admin)
    $_SESSION['error'] = "Too many admin login attempts. Please try again in 15 minutes.";
    header("Location: login");
    exit();
}

// Security: Check if user is already logged in as admin
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && $user['role'] === 'admin') {
        header("Location: dashboard");
        exit();
    } else {
        // If logged in but not admin, logout first
        session_destroy();
    }
}

// Security: Generate CSRF token for admin login
if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Security: CSRF validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
        // Regenerate token after failed attempt
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    } else {
        // Security: Sanitize inputs
        $email = sanitizeStrict(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        
        // Security: Input validation
        if (empty($email) || empty($password)) {
            $error = "Please fill in all fields";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format";
        } elseif (strlen($password) > 256) {
            $error = "Password too long";
        } else {
            // Check if user exists and is admin
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    // Security: Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    // Set secure session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['admin_login_time'] = time();
                    $_SESSION['admin_last_activity'] = time();
                    
                    // Security: Log successful admin login
                    logSecurityEvent('ADMIN_LOGIN_SUCCESS', [
                        'user_id' => $user['id'],
                        'email' => $user['email'],
                        'ip' => $_SERVER['REMOTE_ADDR'],
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                    ]);
                    
                    // Security: Clear CSRF token after successful login
                    unset($_SESSION['admin_csrf_token']);
                    
                    header("Location: dashboard");
                    exit();
                } else {
                    $error = "Invalid password";
                    // Security: Log failed admin login attempt
                    logSecurityEvent('ADMIN_LOGIN_FAILED', [
                        'email' => $email,
                        'ip' => $_SERVER['REMOTE_ADDR'],
                        'reason' => 'Invalid password',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                    ]);
                }
            } else {
                $error = "Admin user not found or access denied";
                // Security: Log failed admin login attempt
                logSecurityEvent('ADMIN_LOGIN_FAILED', [
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'reason' => 'User not found or not admin',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ]);
            }
            // Security: Regenerate CSRF token after each attempt
            $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Kenya EduHub</title>
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdnjs.cloudflare.com; img-src 'self' data:;">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Google Sans', 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #202124;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 48px 40px 36px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: #202124;
            margin-bottom: 32px;
        }
        
        .logo-circle {
            width: 50px;
            height: 50px;
            background: #FFD700;
            border: 3px solid #FF6B35;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 2px;
            flex-shrink: 0;
        }
        
        .logo-circle span {
            font-weight: bold;
            font-size: 24px;
        }
        
        .logo p {
            font-size: 16px;
            color: #5f6368;
            margin-bottom: 40px;
        }
        
        .login-form {
            width: 100%;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: #5f6368;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }
        
        .form-control {
            width: 100%;
            padding: 13px 15px;
            font-size: 16px;
            border: 1px solid #dadce0;
            border-radius: 25px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }
        
        .form-control:focus {
            border-color: #FF6B35;
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }
        
        .form-control::placeholder {
            color: #9aa0a6;
        }
        
        .btn-primary {
            width: 100%;
            padding: 12px 24px;
            background: #FF6B35;
            color: white;
            border: 2px solid #FF6B35;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            margin-top: 8px;
        }
        
        .btn-primary:hover {
            background: #FFD700;
            border-color: #FFD700;
            color: #202124;
        }
        
        .btn-primary:active {
            background: #DAA520;
            border-color: #DAA520;
        }
        
        .back-link {
            margin-top: 48px;
            text-align: center;
        }
        
        .back-link a {
            color: #1a73e8;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.2s;
        }
        
        .back-link a:hover {
            color: #1557b0;
        }
        
        .alert {
            width: 100%;
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .alert-danger {
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid #f9dedc;
        }
        
        .alert-success {
            background: #e6f4ea;
            color: #137333;
            border: 1px solid #ceead6;
        }
        
        .alert .btn-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 18px;
            padding: 0;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        
        .alert .btn-close:hover {
            opacity: 1;
        }
        
        .admin-badge {
            background: #FFD700;
            color: #202124;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 24px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .admin-security-notice {
            background: #f8f9fa;
            border: 1px solid #e8eaed;
            color: #5f6368;
            padding: 12px 16px;
            border-radius: 25px;
            margin-bottom: 24px;
            font-size: 13px;
            text-align: center;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9aa0a6;
            cursor: pointer;
            z-index: 10;
            padding: 5px;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 20px;
            width: 20px;
        }
        
        .password-toggle:hover {
            color: #5f6368;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 24px 20px;
                max-width: 100%;
            }
            
            .logo {
                font-size: 1.25rem;
                gap: 0.5rem;
            }
            
            .logo-circle {
                width: 40px;
                height: 40px;
            }
            
            .logo-circle span {
                font-size: 20px;
            }
            
            h1 {
                font-size: 20px !important;
            }
            
            p {
                font-size: 14px !important;
            }
            
            .form-control {
                padding: 12px 14px;
                font-size: 15px;
            }
            
            .btn-primary {
                padding: 11px 20px;
                font-size: 14px;
            }
            
            .back-link {
                margin-top: 32px;
            }
        }
        
        @media (max-width: 360px) {
            .login-container {
                padding: 20px 16px;
            }
            
            .logo {
                font-size: 1.1rem;
                gap: 0.4rem;
            }
            
            .logo-circle {
                width: 36px;
                height: 36px;
            }
            
            .logo-circle span {
                font-size: 18px;
            }
            
            h1 {
                font-size: 18px !important;
            }
            
            .form-control {
                padding: 11px 12px;
                font-size: 14px;
            }
            
            .btn-primary {
                padding: 10px 16px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-circle">
                <span style="font-weight: bold; font-size: 24px;">
                    <span style="color: #FF6B35; font-size: 28px;">K</span><span style="color: #008000; font-size: 24px;">E</span>
                </span>
            </div>
            <span style="color: #FF6B35;">Kenya</span> <span style="color: #008000;">EduHub</span>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
        <?php endif; ?>
        
        <form class="login-form" method="POST">
            <!-- Security: CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" required autocomplete="username" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group" style="position: relative;">
                <label for="password">Password</label>
                <div style="position: relative;">
                    <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password" style="padding-right: 45px;">
                    <span class="password-toggle" onclick="togglePassword()" role="button" tabindex="0" aria-label="Show or hide password">
                        <i class="fas fa-eye" id="passwordToggleIcon"></i>
                    </span>
                </div>
            </div>
            <button type="submit" class="btn-primary">
                Next
            </button>
        </form>
        
        <div class="back-link">
            <a href="../">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
