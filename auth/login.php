<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Include MINIMAL security (won't break anything)
require_once '../includes/security_lite.php';

require_once '../config.php';

// Log page access
logActivity('PAGE_ACCESS', 'Visited login page');

// Check if user is being logged out (session destroyed)
if (!isset($_SESSION['user_id']) && isset($_GET['logout']) && $_GET['logout'] === 'true') {
    // Log logout activity if user was logged in
    if (isset($_SESSION['user_id'])) {
        logActivity('USER_LOGOUT', 'User logged out', [
            'user_id' => $_SESSION['user_id'],
            'user_email' => $_SESSION['user_email'] ?? 'Unknown'
        ]);
    }
    
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
                
                // Set session like original (unchanged)
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'] ?? $user['full_name'];
                $_SESSION['user_role'] = $user['role'] ?? 'user';
                
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
                
                header("Location: ../dashboard/");
                exit();
            } else {
                error_log("Auth Login - Invalid password");
                $errors[] = "Invalid password";
            }
        } else {
            error_log("Auth Login - User not found");
            $errors[] = "Email not found";
        }
        
        $stmt->close();
    }
    } // Close CSRF else block
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
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="../assets/css/auth-animations.css">
    <style>
        /* Base */
        body {
            background: #000000 !important;
            background-image: none !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
        }

        body::before,
        body::after {
            display: none !important;
        }

        html {
            background: #000000 !important;
            background-image: none !important;
        }


        /* Card */
        .login-card {
            background: #000000;
            max-width: 420px;
            width: 100%;
            padding: 3rem 2.5rem 2.5rem;
            border-radius: 0;
            box-shadow: none;
            border: none;
            animation: none;
            user-select: none;
            will-change: auto;
            transform: none;
            contain: layout style paint;
            position: relative;
            overflow: hidden;
            transition: none;
        }

        .login-card::before {
            display: none;
        }

        .login-card:hover {
            transform: none;
            box-shadow: none;
            background: #000000;
            border: none;
        }

        .login-card h3 {
            font-weight: 700;
            color: #666;
            margin-bottom: 1.75rem;
            text-align: center;
            text-shadow: none;
        }

        /* Form inputs */
        input.form-control {
            height: 48px;
            font-size: 1rem;
            border-radius: 0;
            border: 2px solid #fff;
            background: #000;
            color: #fff !important;
            transition: none;
            will-change: auto;
        }

        input.form-control::placeholder {
            color: #888 !important;
            opacity: 1;
        }

        input.form-control:-webkit-autofill,
        input.form-control:-webkit-autofill:hover,
        input.form-control:-webkit-autofill:focus {
            -webkit-text-fill-color: #fff !important;
            -webkit-box-shadow: 0 0 0 1000px #000 inset;
            transition: background-color 5000s ease-in-out 0s;
        }

        input.form-control:focus {
            border: 2px solid #333;
            box-shadow: none;
            outline: none;
            transform: none;
            background: #000;
        }

        /* Password toggle button */
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 4px;
            border-radius: 0;
            transition: none;
            z-index: 10;
        }

        .password-toggle:hover {
            color: #888;
            background-color: #000;
        }

        .password-toggle:focus {
            outline: none;
        }

        /* Button */
        button.btn-primary {
            width: 100%;
            height: 48px;
            font-weight: 700;
            font-size: 1.125rem;
            border-radius: 0;
            background: #000;
            border: 1px solid #333;
            color: #fff;
            transition: none;
            box-shadow: none;
            user-select: none;
            will-change: auto;
            position: relative;
            overflow: hidden;
        }

        button.btn-primary:hover,
        button.btn-primary:focus-visible {
            background: #111;
            transform: none;
            box-shadow: none;
            outline: none;
            border: 1px solid #444;
        }

        /* Link styling */
        p.text-center small {
            color: #666;
            user-select: none;
        }

        p.text-center small a {
            color: #888;
            text-decoration: none;
            font-weight: 600;
            transition: none;
        }

        p.text-center small a:hover,
        p.text-center small a:focus-visible {
            color: #aaa;
            text-decoration: underline;
            outline: none;
        }

        /* Error Message */
        .alert-error {
            background-color: #000;
            color: #ff0000;
            border: 1px solid #ff0000;
            border-radius: 0;
            padding: 0.9rem 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
            box-shadow: none;
            user-select: none;
            animation: none;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Success Message */
        .alert-success {
            background-color: #000;
            color: #666;
            border: 1px solid #333;
            border-radius: 0;
            padding: 0.9rem 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
            box-shadow: none;
            user-select: none;
            animation: none;
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
            
            .login-card:hover {
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
                    <p><?php echo sanitizeOutput($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success" role="alert">
                <p><?php echo sanitizeOutput($_SESSION['success']); unset($_SESSION['success']); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
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
</body>
</html>
