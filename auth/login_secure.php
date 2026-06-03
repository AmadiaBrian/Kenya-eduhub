<?php
// Include security first (before any output or session)
require_once '../includes/security.php';

// Development error handling (override security.php for development)
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Session is already started by security.php
// Check if user is being logged out (session destroyed)
if (!isset($_SESSION['user_id']) && isset($_GET['logout']) && $_GET['logout'] === 'true') {
    // Clear any remaining session data
    $_SESSION = array();
    
    // Redirect to homepage
    header("Location: ../");
    exit();
}

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/");
    exit();
}

// Include database connection and helpers
require_once '../config.php';
require_once '../includes/helpers.php';  

// Check for remember me cookie first
if (!isset($_SESSION['user_id']) && validateRememberCookie($conn)) {
    header("Location: ../dashboard/");
    exit();
}

// Rate limiting for login attempts
$login_identifier = $_SERVER['REMOTE_ADDR'] . '_' . ($email ?? 'unknown');
if (!checkRateLimit($login_identifier, 5, 300)) { // 5 attempts per 5 minutes
    logSecurityEvent("LOGIN_RATE_LIMIT", ["ip" => $_SERVER['REMOTE_ADDR']]);
    $errors[] = "Too many login attempts. Please try again in 5 minutes.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        logSecurityEvent("CSRF_FAILED", ["ip" => $_SERVER['REMOTE_ADDR']]);
        $errors[] = "Invalid request. Please try again.";
    } else {
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password']; // Don't sanitize password
        $remember = isset($_POST['remember']);

        $errors = [];

        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!validateEmail($email)) {
            $errors[] = "Invalid email format";
        }

        if (empty($password)) {
            $errors[] = "Password is required";
        }

        if (empty($errors)) {
            // Use secure query function
            try {
                $stmt = secureQuery($conn, "SELECT * FROM users WHERE email = ?", [$email]);
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    
                    if (password_verify($password, $user['password'])) {
                        // Regenerate session ID on successful login
                        session_regenerate_id(true);
                        
                        // Set session like original
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_name'] = $user['name'] ?? $user['full_name'];
                        $_SESSION['user_role'] = $user['role'] ?? 'user';
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // New token after login
                        
                        // Update last login
                        try {
                            $updateStmt = secureQuery($conn, "UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
                        } catch (Exception $e) {
                            logSecurityEvent("LOGIN_UPDATE_FAILED", ["error" => $e->getMessage()]);
                        }
                        
                        // Handle remember me
                        if ($remember) {
                            setRememberCookie($user['id'], $conn);
                        } else {
                            deleteRememberCookie($user['id'], $conn);
                        }
                        
                        logSecurityEvent("LOGIN_SUCCESS", ["email" => $email]);
                        header("Location: ../dashboard/");
                        exit();
                    } else {
                        logSecurityEvent("LOGIN_FAILED", ["email" => $email, "reason" => "invalid_password"]);
                        $errors[] = "Invalid password";
                    }
                } else {
                    logSecurityEvent("LOGIN_FAILED", ["email" => $email, "reason" => "user_not_found"]);
                    $errors[] = "Email not found";
                }
                
                $stmt->close();
            } catch (Exception $e) {
                logSecurityEvent("LOGIN_ERROR", ["error" => $e->getMessage()]);
                $errors[] = "Login system error. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Free Educational Resources in Kenya</title>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Base */
        body {
            background: #000000 center/cover fixed;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
            color: #fff;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Card */
        .login-card {
            background: #ffffffdd;
            backdrop-filter: saturate(180%) blur(15px);
            max-width: 420px;
            width: 100%;
            padding: 3rem 2.5rem 2.5rem;
            border-radius: 1.25rem;
            box-shadow: 0 20px 45px rgba(0,0,0,0.15);
            border: 1px solid rgba(255 255 255 / 0.3);
            animation: slideUp 0.7s ease forwards;
            user-select: none;
            will-change: transform, opacity;
            transform: translateZ(0);
            contain: layout style paint;
        }

        .login-card h3 {
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 1.75rem;
            text-align: center;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        /* Form inputs */
        input.form-control {
            height: 48px;
            font-size: 1rem;
            border-radius: 0.625rem;
            border: 1.8px solid #ced4da;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            will-change: border-color, box-shadow;
        }

        input.form-control:focus {
            border-color: #4ea1ff;
            box-shadow: 0 0 8px #4ea1ff88;
            outline: none;
            transform: translateZ(0);
        }

        /* Password toggle button */
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: color 0.2s ease, background-color 0.2s ease;
            z-index: 10;
        }

        .password-toggle:hover {
            color: #4ea1ff;
            background-color: rgba(78, 161, 255, 0.1);
        }

        .password-toggle:focus {
            outline: 2px solid #4ea1ff;
            outline-offset: 2px;
        }

        /* Button */
        button.btn-primary {
            width: 100%;
            height: 48px;
            font-weight: 700;
            font-size: 1.125rem;
            border-radius: 0.75rem;
            background: linear-gradient(90deg, #1e3c72, #4ea1ff);
            border: none;
            transition: background 0.3s ease, transform 0.2s ease;
            box-shadow: 0 8px 20px rgb(78 161 255 / 0.5);
            user-select: none;
            will-change: transform, background;
            position: relative;
            overflow: hidden;
        }

        button.btn-primary:hover,
        button.btn-primary:focus-visible {
            background: linear-gradient(90deg, #4ea1ff, #1e3c72);
            transform: scale(1.05) translateZ(0);
            box-shadow: 0 12px 30px rgb(78 161 255 / 0.7);
            outline-offset: 3px;
            outline: 3px solid #1e3c72;
        }

        /* Link styling */
        p.text-center small {
            color: #495057;
            user-select: none;
        }

        p.text-center small a {
            color: #4ea1ff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.25s ease;
        }

        p.text-center small a:hover,
        p.text-center small a:focus-visible {
            color: #1e3c72;
            text-decoration: underline;
            outline-offset: 2px;
            outline: 2px solid #1e3c72;
            border-radius: 4px;
        }

        /* Error Message */
        .alert-error {
            background-color: #f8d7da;
            color: #842029;
            border-radius: 0.625rem;
            padding: 0.9rem 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
            box-shadow: 0 0 8px #f5c2c7;
            user-select: none;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Success Message */
        .alert-success {
            background-color: #d1e7dd;
            color: #0f5132;
            border-radius: 0.625rem;
            padding: 0.9rem 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
            box-shadow: 0 0 8px #badbcc;
            user-select: none;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Animations */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px) translateZ(0);
            }
            to {
                opacity: 1;
                transform: translateY(0) translateZ(0);
            }
        }

        /* Logo */
        .logo-img {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .logo-img img {
            max-width: 120px;
            height: auto;
            filter: drop-shadow(0 0 6px rgba(0, 0, 0, 0.2));
            will-change: transform;
            transform: translateZ(0);
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 2rem 1.5rem 2rem;
            }
            button.btn-primary {
                font-size: 1rem;
                height: 44px;
            }
        }

        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            .login-card {
                animation: none;
            }
            
            button.btn-primary:hover,
            button.btn-primary:focus-visible {
                transform: none;
            }
            
            .alert-error {
                animation: none;
            }
            
            .alert-success {
                animation: none;
            }
        }

        /* Focus indicators */
        .form-control:focus-visible,
        .btn-primary:focus-visible,
        .password-toggle:focus-visible {
            outline: 3px solid #4ea1ff;
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <main class="login-card" role="main" aria-label="User Login Form">
        <div class="logo-img">
            <img src="../assets/favicon.ico" alt="Kenya EduHub Logo">
        </div>
        <h3>Login to Your Account</h3>

        <?php if (!empty($errors)): ?>
            <div class="alert-error" role="alert" aria-live="assertive">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success" role="alert">
                <p><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
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
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
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

        <p class="text-center mt-4 small">
            <a href="forgot_password.php" class="forgot-password-link">Forgot Password?</a>
        </p>

        <p class="text-center small mt-3">
            Need to verify your email? <a href="verify-code.php" aria-label="Verify email address">Verify Email</a>
        </p>

        <p class="text-center small mt-3">
            Don't have an account? <a href="register.php" aria-label="Create new account">Register here</a>
        </p>        
        <p class="text-center small mt-3">
            <a href="?logout=true" aria-label="Logout from any account">Logout</a>
        </p>
    </main>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById("password");
            const eyeIcon = document.getElementById("eye-icon");

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eyeIcon.classList.remove("fa-eye");
                eyeIcon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                eyeIcon.classList.remove("fa-eye-slash");
                eyeIcon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>
