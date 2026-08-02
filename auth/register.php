<?php
// Session is already started by the router (auth/index.php)
// Include MINIMAL security (won't break anything)
require_once '../includes/security_lite.php';
require_once '../config/database.php';
require_once '../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Registration form submitted");
    error_log("POST data: " . print_r($_POST, true));
    
    $errors = []; // Initialize errors array
    
    // CSRF protection (minimal, won't break anything)
    if (!isset($_POST['csrf_token']) || !validateCSRFLite($_POST['csrf_token'])) {
        $errors[] = "Session expired. Please refresh.";
        error_log("CSRF validation failed");
    } else {
        error_log("CSRF validation passed");
        // Rate limiting for registration
        $reg_identifier = $_SERVER['REMOTE_ADDR'] . '_registration';
        if (!checkRateLimit($reg_identifier, 3, 900)) { // 3 attempts per 15 minutes
            $errors[] = "Too many registration attempts. Please try again in 15 minutes.";
        } else {
            $fullName = sanitizeStrict($_POST['full_name']); // XSS protection
            $email = sanitizeStrict($_POST['email']); // XSS protection
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];

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
                error_log("Form validation passed for email: $email");
                
                // Check if email exists in deleted_accounts table
                try {
                    $deleted_account_check = $conn->prepare("SELECT * FROM deleted_accounts WHERE email = ? ORDER BY deleted_at DESC LIMIT 1");
                    $deleted_account_check->bind_param("s", $email);
                    $deleted_account_check->execute();
                    $deleted_result = $deleted_account_check->get_result();
                    
                    error_log("Deleted accounts check result: " . $deleted_result->num_rows . " records found");
                    
                    if ($deleted_result->num_rows > 0) {
                        $deleted_account = $deleted_result->fetch_assoc();
                        // Store deleted account info for restore page
                        $_SESSION['deleted_account'] = $deleted_account;
                        $_SESSION['restore_email'] = $email;
                        $_SESSION['restore_name'] = $fullName;
                        $_SESSION['restore_password'] = $password;
                        error_log("Redirecting to restore page for email: $email");
                        header("Location: restore");
                        exit();
                    }
                } catch (Exception $e) {
                    error_log("Deleted accounts check failed: " . $e->getMessage());
                    // Continue with normal registration if table doesn't exist
                }
                
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
                    error_log("Registration successful for email: $email");
                    header("Location: verify");
                    exit();
                } else {
                    // Handle API errors
                    $error_message = isset($result['error']) ? $result['error'] : 'Registration failed';
                    $errors[] = $error_message;
                    error_log("Registration API failed: $error_message");
                    
                    // Debug: Add more specific error info
                    if ($curlError) {
                        $errors[] = "cURL Error: $curlError";
                    }
                    if ($httpCode !== 200) {
                        $errors[] = "HTTP Error: $httpCode";
                    }
                    error_log("Registration Debug - Not redirecting, showing errors");
                }
            } else {
                error_log("Form validation failed. Errors: " . print_r($errors, true));
            } // Close empty errors check
        } // Close rate limiting else block
    } // Close CSRF else block
} // Close POST check
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Register - Free Educational Resources in Kenya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

        .register-card {
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

        .register-card h3 {
            font-size: 24px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 8px;
            text-align: center;
        }

        .register-form {
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
            background: #e55a2b;
            border-color: #e55a2b;
            color: white;
        }

        .alert-error {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid #fce8e6;
        }

        .alert-success {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            background: #e6f4ea;
            color: #137333;
            border: 1px solid #e6f4ea;
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

        .register-links {
            text-align: center;
            margin-top: 24px;
        }

        .register-links a {
            color: #FF6B35;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .register-links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .register-card {
                padding: 32px 24px 24px;
            }
        }
    </style>
</head>
<body>
    <main class="register-card" role="main" aria-label="User Registration Form">
        <div class="auth-brand-logo" aria-label="Kenya EduHub Logo">
            <div class="logo-circle">
                <span style="color: #FF6B35; font-size: 28px;">K</span><span style="color: #008000; font-size: 24px;">E</span>
            </div>
            <span class="brand-text"><span style="color: #FF6B35;">Kenya</span> <span style="color: #008000;">EduHub</span></span>
        </div>

        <h3>Create Your Account</h3>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success" role="alert">
                <p><?php echo sanitizeOutput($_SESSION['success']); unset($_SESSION['success']); ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['restore_error'])): ?>
            <div class="alert-error" role="alert">
                <p><?php echo sanitizeOutput($_SESSION['restore_error']); unset($_SESSION['restore_error']); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert-error" role="alert">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo sanitizeOutput($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="register-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFLite(); ?>">

            <div class="form-group">
                <label for="full_name">Full name</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    class="form-control"
                    placeholder="Full Name"
                    required
                    autocomplete="name"
                    autofocus
                    value="<?php echo isset($_POST['full_name']) ? sanitizeOutput($_POST['full_name']) : ''; ?>"
                />
            </div>

            <div class="form-group">
                <label for="email">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="Email"
                    required
                    autocomplete="email"
                    value="<?php echo isset($_POST['email']) ? sanitizeOutput($_POST['email']) : ''; ?>"
                />
            </div>

            <div class="form-group position-relative">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control pe-5"
                    placeholder="Password"
                    required
                    autocomplete="new-password"
                />
                <span class="password-toggle" onclick="togglePassword('password')">
                    <i class="fa-solid fa-eye" id="eye-icon-password"></i>
                </span>
            </div>

            <div class="form-group position-relative">
                <label for="confirm_password">Confirm Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    class="form-control pe-5"
                    placeholder="Confirm Password"
                    required
                    autocomplete="new-password"
                />
                <span class="password-toggle" onclick="togglePassword('confirm_password')">
                    <i class="fa-solid fa-eye" id="eye-icon-confirm_password"></i>
                </span>
            </div>

            <button type="submit" class="btn btn-primary" aria-label="Create account">
                Create Account
            </button>
        </form>

        <div class="register-links">
            Already have an account? <a href="login">Login</a>
        </div>
    </main>

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

        // Prevent form double submission
        document.querySelector('.register-form').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn.disabled) {
                e.preventDefault();
                return false;
            }
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
        });
    </script>
</body>
</html>
