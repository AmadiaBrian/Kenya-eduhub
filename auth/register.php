<?php
session_start();
// Include MINIMAL security (won't break anything)
require_once '../includes/security_lite.php';
require_once '../config/database.php';
require_once '../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection (minimal, won't break anything)
    if (!isset($_POST['csrf_token']) || !validateCSRFLite($_POST['csrf_token'])) {
        $errors[] = "Session expired. Please refresh.";
    } else {
        // Rate limiting for registration
        $reg_identifier = $_SERVER['REMOTE_ADDR'] . '_registration';
        if (!checkRateLimit($reg_identifier, 3, 900)) { // 3 attempts per 15 minutes
            $errors[] = "Too many registration attempts. Please try again in 15 minutes.";
        } else {
            $fullName = sanitizeStrict($_POST['full_name']); // XSS protection
            $email = sanitizeStrict($_POST['email']); // XSS protection
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];

            $errors = [];

            if (empty($fullName)) {
                $errors[] = "Full name is required";
            } elseif (strlen($fullName) < 2) {
                $errors[] = "Full name must be at least 2 characters";
            }

            if (empty($email)) {
                $errors[] = "Email is required";
            } elseif (!validateEmailLite($email)) {
                $errors[] = "Invalid email format";
            }

            if (empty($password)) {
                $errors[] = "Password is required";
            } else {
                // Password strength validation
                $passwordErrors = validatePasswordStrength($password);
                if (!empty($passwordErrors)) {
                    $errors = array_merge($errors, $passwordErrors);
                }
            }

            if (empty($confirmPassword)) {
                $errors[] = "Password confirmation is required";
            }

            if ($password !== $confirmPassword) {
                $errors[] = "Passwords do not match";
            }

            if (empty($errors)) {
        // Call API to register user
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        
        // Get the correct base path - remove /auth from the path
        $currentPath = dirname($_SERVER['PHP_SELF']);
        $basePath = str_replace('/auth', '', $currentPath);
        $basePath = rtrim($basePath, '/\\');
        
        $apiUrl = $protocol . '://' . $host . $basePath . '/api/register.php';
        
        $data = [
            'name' => $fullName,
            'email' => $email,
            'password' => $password
        ];
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Debug information
        error_log("Registration API Call - HTTP Code: $httpCode");
        error_log("Registration API Call - Response: $response");
        error_log("Registration API Call - cURL Error: $curlError");
        
        $result = json_decode($response, true);
        
        // Debug: Show what we got back
        error_log("Registration Debug - Parsed Result: " . print_r($result, true));
        error_log("Registration Debug - Success Check: " . (isset($result['success']) && $result['success'] ? 'TRUE' : 'FALSE'));
        
        if ($httpCode === 200 && isset($result['success']) && $result['success']) {
            // Store email for verification page
            $_SESSION['verification_email'] = $email;
            $_SESSION['success'] = $result['message'];
            error_log("Registration Debug - Redirecting to verify.php");
            header("Location: verify.php");
            exit();
        } else {
            $errors[] = $result['message'] ?? "Registration failed. Please try again.";
            // Debug: Add more specific error info
            if ($curlError) {
                $errors[] = "cURL Error: $curlError";
            }
            if ($httpCode !== 200) {
                $errors[] = "HTTP Error: $httpCode";
            }
            error_log("Registration Debug - Not redirecting, showing errors");
        }
    } // Close registration logic
        } // Close rate limiting else block
    } // Close CSRF else block
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Free Educational Resources in Kenya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth-animations.css">
    <style>
        /* Base */
        body {
            background: #000000 !important;
            background-image: none !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
            color: #fff;
            will-change: auto;
            contain: layout style paint;
            position: relative;
            overflow: hidden;
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
        .register-card {
            background: #000000;
            max-width: 480px;
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

        .register-card::before {
            display: none;
        }

        .register-card:hover {
            transform: none;
            box-shadow: none;
            background: #000000;
            border: none;
        }

        .register-card h3 {
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

        /* Success Message */
        .alert-success {
            background-color: #000;
            color: #0f5132;
            border: 1px solid #0f5132;
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

        /* Form groups */
        .mb-4 {
            margin-bottom: 1.5rem;
        }

        .position-relative {
            position: relative;
        }

        .visually-hidden {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }

        /* Invalid field styling */
        .form-control.is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }

        .form-control.is-invalid:focus {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }

        .invalid-feedback {
            display: none;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            color: #dc3545;
            animation: slideDown 0.3s ease-out;
        }

        .invalid-feedback[style*="block"] {
            display: block !important;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .register-card {
                padding: 2rem 1.5rem 2rem;
            }
            button.btn-primary {
                font-size: 1rem;
                height: 44px;
            }
        }

        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            .register-card {
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
        :root {
            --primary-orange: #FF6B35;
            --primary-gold: #FFD700;
        }

        .auth-brand-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
        }

        .auth-brand-logo .brand-text {
            line-height: 1;
        }

        .register-card h3 {
            color: #ffffff;
        }

        .register-card h3::first-letter {
            color: var(--primary-orange);
        }
    </style>
</head>
<body>
    <main class="register-card" role="main" aria-label="User Registration Form">
        <div class="logo-img">
            <div class="auth-brand-logo" aria-label="Kenya EduHub Logo">
                <div style="width: 50px; height: 50px; background: var(--primary-gold); border: 3px solid var(--primary-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 2px;">
                    <span style="font-weight: bold; font-size: 24px;">
                        <span style="color: var(--primary-orange); font-size: 28px;">K</span><span style="color: #008000; font-size: 24px;">E</span>
                    </span>
                </div>
                <span class="brand-text"><span style="color: var(--primary-orange);">Kenya</span> <span style="color: #008000;">EduHub</span></span>
            </div>
        </div>
        <h3>Create Your Account</h3>

        <?php 
// Only show system-level errors, not field validation errors
$systemErrors = [];
if (isset($errors) && is_array($errors)) {
    $systemErrors = array_filter($errors, function($error) {
        return !in_array($error, [
        'Full name is required',
        'Full name must be at least 2 characters',
        'Email is required',
        'Invalid email format',
        'Password is required',
        'Password confirmation is required',
        'Passwords do not match',
        'Be at least 8 characters long',
        'Contain at least one uppercase letter',
        'Contain at least one lowercase letter',
        'Contain at least one number',
        'Contain at least one special character (!@#$%^&*)'
    ]);
    });
}

if (!empty($systemErrors)): 
?>
            <div class="alert-error" role="alert" aria-live="assertive">
                <?php foreach ($systemErrors as $error): ?>
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
                <label for="full_name" class="visually-hidden">Full name</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    class="form-control <?php echo (isset($errors) && in_array('Full name is required', $errors)) ? 'is-invalid' : ''; ?>"
                    placeholder="Full Name"
                    required
                    autocomplete="name"
                    autofocus
                    value="<?php echo isset($_POST['full_name']) ? sanitizeOutput($_POST['full_name']) : ''; ?>"
                />
                <?php if (isset($errors) && in_array('Full name is required', $errors)): ?>
                    <div class="invalid-feedback" style="display: block; color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem;">
                        <i class="fas fa-exclamation-circle"></i> Full name is required
                    </div>
                <?php endif; ?>
                <?php if (isset($errors) && in_array('Full name must be at least 2 characters', $errors)): ?>
                    <div class="invalid-feedback" style="display: block; color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem;">
                        <i class="fas fa-exclamation-circle"></i> Full name must be at least 2 characters
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label for="email" class="visually-hidden">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control <?php echo (isset($errors) && (in_array('Email is required', $errors) || in_array('Invalid email format', $errors))) ? 'is-invalid' : ''; ?>"
                    placeholder="Email"
                    required
                    autocomplete="email"
                    value="<?php echo isset($_POST['email']) ? sanitizeOutput($_POST['email']) : ''; ?>"
                />
                <?php if (isset($errors) && in_array('Email is required', $errors)): ?>
                    <div class="invalid-feedback" style="display: block; color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem;">
                        <i class="fas fa-exclamation-circle"></i> Email is required
                    </div>
                <?php endif; ?>
                <?php if (isset($errors) && in_array('Invalid email format', $errors)): ?>
                    <div class="invalid-feedback" style="display: block; color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem;">
                        <i class="fas fa-exclamation-circle"></i> Invalid email format
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-4 position-relative">
                <label for="password" class="visually-hidden">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control pe-5 <?php echo (isset($errors) && in_array('Password is required', $errors)) ? 'is-invalid' : ''; ?>"
                    placeholder="Password"
                    required
                    autocomplete="new-password"
                />
                <span class="password-toggle" onclick="togglePassword('password')">
                    <i class="fa-solid fa-eye" id="eye-icon-password"></i>
                </span>
                <?php if (isset($errors) && in_array('Password is required', $errors)): ?>
                    <div class="invalid-feedback" style="display: block; color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem;">
                        <i class="fas fa-exclamation-circle"></i> Password is required
                    </div>
                <?php endif; ?>
                <?php 
// Show specific password requirements
$passwordErrors = [];
if (isset($errors) && is_array($errors)) {
    $passwordErrors = array_intersect($errors, [
    'Be at least 8 characters long', 
    'Contain at least one uppercase letter', 
    'Contain at least one lowercase letter', 
    'Contain at least one number', 
    'Contain at least one special character (!@#$%^&*)'
]);
}

if (!empty($passwordErrors)): 
?>
                    <div class="invalid-feedback" style="display: block; color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem;">
                        <i class="fas fa-exclamation-circle"></i> Password must:
                        <ul style="margin: 0.25rem 0 0 1.25rem; padding: 0; font-size: 0.8rem;">
                            <?php foreach ($passwordErrors as $error): ?>
                                <li style="margin-bottom: 0.25rem;"><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-4 position-relative">
                <label for="confirm_password" class="visually-hidden">Confirm Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    class="form-control pe-5 <?php echo (isset($errors) && in_array('Passwords do not match', $errors)) ? 'is-invalid' : ''; ?>"
                    placeholder="Confirm Password"
                    required
                    autocomplete="new-password"
                />
                <span class="password-toggle" onclick="togglePassword('confirm_password')">
                    <i class="fa-solid fa-eye" id="eye-icon-confirm_password"></i>
                </span>
                <?php if (isset($errors) && in_array('Passwords do not match', $errors)): ?>
                    <div class="invalid-feedback" style="display: block; color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem;">
                        <i class="fas fa-exclamation-circle"></i> Passwords do not match
                    </div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary" aria-label="Create your account">
                Register
            </button>
        </form>

        <p class="text-center mt-4 small">
            Already have an account? <a href="login.php" aria-label="Sign in to your account">Sign In</a>
        </p>
    </main>

    <script src="../assets/js/admin-shortcut.js"></script>
    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const eyeIcon = document.getElementById("eye-icon-" + fieldId);

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

        // Form validation
        // Remove JavaScript validation - let PHP handle it with inline errors
        document.querySelector('form').addEventListener('submit', function(e) {
            // Allow form to submit - PHP will handle validation and show inline errors
            return true;
        });
    </script>
</body>
</html>
