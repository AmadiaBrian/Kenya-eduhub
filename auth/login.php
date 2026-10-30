<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session is already started by the router (auth/index.php)

// Include MINIMAL security (won't break anything)
require_once '../includes/security_lite.php';

require_once '../config.php';
require_once '../includes/helpers.php';

// Log page access
logActivity('PAGE_ACCESS', 'Visited login page');

// Check if user is being logged out (session destroyed)
if (!isset($_SESSION['user_id']) && isset($_GET['logout']) && $_GET['logout'] === 'true') {
    // Clear any remaining session data
    $_SESSION = array();
    
    // Redirect to homepage via logout route
    header("Location: logout");
    exit();
}

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    // Check if user is admin, redirect to admin dashboard
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && $user['role'] === 'admin') {
        // Check if there's a redirect URL stored first
        if (isset($_SESSION['redirect_after_login'])) {
            $redirect_url = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            header("Location: ../" . $redirect_url);
            exit;
        }
        header("Location: ../admin");
        exit;
    } else {
        header("Location: ../dashboard");
        exit;
    }
}

// Include database connection and helpers
require_once '../config.php';
// helpers.php already included above  

// Check for remember me cookie first
if (!isset($_SESSION['user_id']) && validateRememberCookie($conn)) {
    // Check if the remembered user is admin for proper redirect
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && $user['role'] === 'admin') {
        // Check if there's a redirect URL stored first
        if (isset($_SESSION['redirect_after_login'])) {
            $redirect_url = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            header("Location: ../" . $redirect_url);
            exit;
        }
        header("Location: ../admin");
        exit;
    }
    
    // Check if there's a redirect URL stored
    if (isset($_SESSION['redirect_after_login'])) {
        $redirect_url = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        header("Location: ../" . $redirect_url);
        exit();
    }
    
    header("Location: ../dashboard");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection (minimal, won't break anything)
    if (!isset($_POST['csrf_token']) || !validateCSRFLite($_POST['csrf_token'])) {
        $errors[] = "Session expired. Please refresh.";
    } else {
        $email = sanitizeStrict($_POST['email']); // XSS protection for email
        $password = $_POST['password']; // Don't sanitize password
        $remember = isset($_POST['remember']);

        $errors = [];

        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!validateEmailLite($email)) { // Add email validation
            $errors[] = "Invalid email format";
        }

        if (empty($password)) {
            $errors[] = "Password is required";
        }

        // Add simple rate limiting (won't break anything)
        $login_identifier = $_SERVER['REMOTE_ADDR'] . '_' . ($email ?? 'unknown');
        if (!checkRateLimit($login_identifier, 10, 300)) { // 10 attempts per 5 minutes
            $errors[] = "Too many login attempts. Please try again in 5 minutes.";
        }

        if (empty($errors)) {
            // Check if this email belongs to an admin user - if so, skip login attempt tracking
            $check_admin_stmt = $conn->prepare("SELECT role FROM users WHERE email = ?");
            $check_admin_stmt->bind_param("s", $email);
            $check_admin_stmt->execute();
            $admin_result = $check_admin_stmt->get_result();
            
            $is_admin = false;
            if ($admin_result->num_rows > 0) {
                $admin_user = $admin_result->fetch_assoc();
                $is_admin = ($admin_user['role'] === 'admin');
            }
            
            // Check login attempts (only for non-admin users)
            if (!$is_admin) {
                $login_check = checkLoginAttempts($conn, $email);
                
                if (!$login_check['can_login']) {
                    if ($login_check['locked']) {
                        $locked_time = strtotime($login_check['locked_until']);
                        $current_time = time();
                        $remaining_minutes = ceil(($locked_time - $current_time) / 60);
                        $errors[] = "Account is locked due to too many failed attempts. Please try again in {$remaining_minutes} minutes.";
                    } else {
                        $errors[] = "Too many failed login attempts. Please try again later.";
                    }
                }
            }
            
            if (empty($errors)) {
                error_log("Auth Login - Original style authentication for: " . $email);
                
                // Use original MySQLi approach (unchanged)
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    error_log("Auth Login - User found: " . $user['email']);
                    
                    if (password_verify($password, $user['password'])) {
                        error_log("Auth Login - Password verified, login successful");

                        // Check if user is verified
                        if ($user['is_verified'] == 0) {
                            error_log("Auth Login - User not verified, redirecting to verification page");

                            // Set verification session variables
                            $_SESSION['verification_email'] = $user['email'];
                            $_SESSION['verification_phone'] = $user['phone'] ?? null;

                            // Redirect to verification page
                            header("Location: verify");
                            exit();
                        }

                        // Record successful login (only for non-admin users)
                        if (!$is_admin) {
                            recordLoginAttempt($conn, $email, true);
                        }
                        
                        // Set session like original (unchanged)
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_name'] = $user['name'] ?? $user['full_name'];
                        $_SESSION['user_role'] = $user['role'] ?? 'user';
                        
                        // Apply session timeout (admins get extended timeout)
                        if ($is_admin) {
                            $_SESSION['session_timeout'] = 480; // 8 hours for admins
                        } else {
                            $session_timeout = getSessionTimeout($conn);
                            $_SESSION['session_timeout'] = $session_timeout;
                        }
                        $_SESSION['last_activity'] = time();
                        
                        // Update last login (unchanged)
                        try {
                            $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                            $updateStmt->bind_param("i", $user['id']);
                            $updateStmt->execute();
                        } catch (Exception $e) {
                            error_log("Failed to update last login: " . $e->getMessage());
                        }
                        
                        // Handle remember me (unchanged)
                        if ($remember) {
                            setRememberCookie($user['id'], $conn);
                        } else {
                            deleteRememberCookie($user['id'], $conn);
                        }

                        // If admin, show choice modal instead of redirecting
                        if ($user['role'] === 'admin') {
                            $_SESSION['pending_admin_redirect'] = true;
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['user_name'] = $user['name'] ?? $user['full_name'];
                            $_SESSION['user_role'] = $user['role'] ?? 'user';
                            $_SESSION['session_timeout'] = 480;
                            $_SESSION['last_activity'] = time();
                            // Continue to render page with modal (don't exit)
                        } else {
                            // Check if there's a redirect URL stored
                            if (isset($_SESSION['redirect_after_login'])) {
                                $redirect_url = $_SESSION['redirect_after_login'];
                                unset($_SESSION['redirect_after_login']);
                                header("Location: ../" . $redirect_url);
                                exit();
                            }
                            // Redirect based on user role
                            header("Location: ../dashboard");
                            exit();
                        }
                    } else {
                        error_log("Auth Login - Invalid password");
                        // Record failed login attempt (only for non-admin users)
                        if (!$is_admin) {
                            recordLoginAttempt($conn, $email, false);
                            
                            $login_check = checkLoginAttempts($conn, $email);
                            $remaining_attempts = getMaxLoginAttempts($conn) - $login_check['attempts'];
                            $errors[] = "Invalid password. {$remaining_attempts} attempts remaining.";
                        } else {
                            $errors[] = "Invalid password";
                        }
                    }
                } else {
                    error_log("Auth Login - User not found");
                    // Record failed login attempt (only for non-admin users)
                    if (!$is_admin) {
                        recordLoginAttempt($conn, $email, false);
                    }
                    $errors[] = "Email not found";
                }
                
                $stmt->close();
            }
        }
    } // Close CSRF else block
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <meta property="og:image" content="https://kenyaeduhub.kesug.com/assets/favicon.ico">
    <link rel="icon" type="image/x-icon" href="https://kenyaeduhub.kesug.com/assets/favicon.ico" />
    <link rel="apple-touch-icon" href="https://kenyaeduhub.kesug.com/assets/favicon.ico">
    <title>Login - Kenya EduHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="../assets/css/auth-animations.css">

    <!-- Breadcrumb Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "Home",
                "item": "https://kenyaeduhub.kesug.com/"
            },
            {
                "@type": "ListItem",
                "position": 2,
                "name": "Login",
                "item": "https://kenyaeduhub.kesug.com/auth/login"
            }
        ]
    }
    </script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #F0F4F9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Google Sans', 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #202124;
        }
        
        .login-card {
            width: 100%;
            max-width: 450px;
            padding: 48px 40px 36px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .auth-brand-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: #202124;
            margin-bottom: 8px;
            margin-top: -10px;
        }
        
        .auth-brand-logo .logo-circle {
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
        
        .auth-brand-logo .logo-circle span {
            font-weight: bold;
            font-size: 24px;
        }
        
        .login-card h1 {
            font-size: 24px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 8px;
            text-align: center;
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
            background: #F0F4F9;
        }

        .form-control:focus {
            border-color: #FF6B35;
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
            background: #F0F4F9;
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
            background: #e55a2b;
            border-color: #e55a2b;
            color: white;
        }
        
        .alert-error {
            padding: 12px 16px;
            border-radius: 25px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid #fce8e6;
        }

        .alert-success {
            padding: 12px 16px;
            border-radius: 25px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            background: #e6f4ea;
            color: #137333;
            border: 1px solid #e6f4ea;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            border: 2px solid #dadce0;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 8px;
        }
        
        .form-check-label {
            font-size: 14px;
            color: #5f6368;
            cursor: pointer;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #5f6368;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: color 0.2s;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: #FF6B35;
        }
        
        .login-links {
            text-align: center;
            margin-top: 24px;
        }
        
        .login-links a {
            color: #FF6B35;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .login-links a:hover {
            text-decoration: underline;
        }

        /* Responsive - Mobile */
        @media (max-width: 768px) {
            .login-card {
                padding: 32px 24px 24px;
                max-width: 100%;
            }

            .auth-brand-logo {
                font-size: 1.3rem;
            }

            .auth-brand-logo .logo-circle {
                width: 45px;
                height: 45px;
            }

            .login-card h3 {
                font-size: 20px;
            }

            .form-control {
                font-size: 16px; /* Prevent zoom on iOS */
            }

            .btn-primary {
                font-size: 14px;
                padding: 12px 20px;
            }

            .login-links {
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 24px 20px 20px;
            }

            .auth-brand-logo {
                font-size: 1.2rem;
            }

            .auth-brand-logo .logo-circle {
                width: 40px;
                height: 40px;
            }

            .login-card h3 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body class="login-page">
    <main class="login-card" role="main" aria-label="User Login Form">
        <div class="auth-brand-logo" aria-label="Kenya EduHub Logo">
            <?php require_once '../includes/logo.php'; ?>
        </div>
        
        <br>

        <h1>Login to Your Account</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert-error" role="alert" aria-live="assertive">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo sanitizeOutput($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success" role="alert">
                <p><?php echo sanitizeOutput($_SESSION['success']); unset($_SESSION['success']); ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['restore_success'])): ?>
            <div class="alert-success" role="alert">
                <p><?php echo sanitizeOutput($_SESSION['restore_success']); unset($_SESSION['restore_success']); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate class="login-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFLite(); ?>">

            <div class="mb-4">
                <label for="email" class="visually-hidden">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="Email"
                    required
                    autocomplete="email"
                    autofocus
                    value="<?php echo isset($_POST['email']) ? sanitizeOutput($_POST['email']) : ''; ?>"
                />
            </div>

            <div class="mb-4 position-relative">
                <label for="password" class="visually-hidden">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control pe-5"
                    placeholder="Password"
                    required
                    autocomplete="current-password"
                />
                <span class="password-toggle" onclick="togglePassword()">
                    <i class="fa-solid fa-eye" id="eye-icon"></i>
                </span>
            </div>

            <button type="submit" class="btn btn-primary" aria-label="Login to your account">
                Login
            </button>
        </form>

        <div style="margin-top: 16px;">
            <p style="font-size: 13px; color: #5f6368; margin-bottom: 8px;">Don't have an account?</p>
            <a href="register" aria-label="Create new account" style="color: #FF6B35; text-decoration: none;">Register here</a>
        </div>

        <div style="margin-top: 16px;">
            <p style="font-size: 13px; color: #5f6368; margin-bottom: 8px;">Forgot your password?</p>
            <a href="forgot_password" class="forgot-password-link" style="color: #FF6B35; text-decoration: none;">Reset Password</a>
        </div>

        <div style="margin-top: 16px;">
            <p style="font-size: 13px; color: #5f6368; margin-bottom: 8px;">Need a new verification code?</p>
            <a href="resend" aria-label="Resend verification code" style="color: #FF6B35; text-decoration: none;">Resend Code</a>
        </div>

        <!-- Google Login Divider -->
    </main>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById("password");
            const eyeIcon = document.getElementById("eye-icon");

            if (!passwordInput || !eyeIcon) return;

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eyeIcon.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                passwordInput.type = "password";
                eyeIcon.classList.replace("fa-eye-slash", "fa-eye");
            }
        }
    </script>

    <!-- Footer -->
    <?php require_once '../includes/copywrite.php'; ?>

    <!-- Admin Dashboard Choice Modal -->
    <?php if (isset($_SESSION['pending_admin_redirect'])): ?>
    <div id="adminChoiceModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </div>
                <div class="modal-logo">
                    <?php require_once '../includes/logo.php'; ?>
                </div>
                <h2>Admin Account Detected</h2>
                <p>You're logging in as an administrator. Which dashboard would you like to access?</p>
            </div>
            <div class="modal-body">
                <button onclick="redirectToDashboard('admin')" class="modal-btn primary">
                    <span>Admin Dashboard</span>
                </button>
                <button onclick="redirectToDashboard('user')" class="modal-btn secondary">
                    <span>User Dashboard</span>
                </button>
            </div>
        </div>
    </div>
    <style>
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
            max-width: 400px;
            width: 90%;
            animation: slideUp 0.2s ease;
            overflow: hidden;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            text-align: center;
            padding: 24px 24px 16px;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: transparent;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #5f6368;
            transition: background 0.2s ease;
        }

        .modal-close:hover {
            background: #f1f3f4;
        }

        .modal-logo {
            margin-bottom: 16px;
            display: flex;
            justify-content: center;
        }

        .modal-logo img {
            height: 48px;
            width: auto;
        }

        .modal-header h2 {
            font-size: 20px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 8px;
        }

        .modal-header p {
            font-size: 14px;
            color: #5f6368;
            line-height: 1.5;
        }

        .modal-body {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 16px 24px 24px;
        }

        .modal-btn {
            padding: 12px 24px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease, box-shadow 0.2s ease;
            border: none;
            font-family: 'Google Sans', 'Roboto', 'Segoe UI', Arial, sans-serif;
        }

        .modal-btn.primary {
            background: #FF6B35;
            color: #ffffff;
        }

        .modal-btn.primary:hover {
            background: #e55a2b;
            box-shadow: 0 2px 8px rgba(255, 107, 53, 0.3);
        }

        .modal-btn.secondary {
            background: #F0F4F9;
            color: #202124;
            border: 2px solid #e8eaed;
        }

        .modal-btn.secondary:hover {
            background: #e8eaed;
            border-color: #FF6B35;
        }

        @media (max-width: 480px) {
            .modal-content {
                max-width: 100%;
                margin: 0 16px;
            }

            .modal-header {
                padding: 20px 20px 12px;
            }

            .modal-header h2 {
                font-size: 18px;
            }

            .modal-header p {
                font-size: 13px;
            }

            .modal-logo img {
                height: 40px;
            }

            .modal-body {
                padding: 12px 20px 20px;
            }

            .modal-btn {
                padding: 12px 20px;
            }
        }
    </style>
    <script>
        function redirectToDashboard(type) {
            if (type === 'admin') {
                window.location.href = '../admin';
            } else {
                window.location.href = '../dashboard';
            }
        }

        function closeModal() {
            document.getElementById('adminChoiceModal').style.display = 'none';
        }
    </script>
    <?php
        unset($_SESSION['pending_admin_redirect']);
    ?>
    <?php endif; ?>
</body>
</html>
