<?php
// Session is already started by the router (auth/index.php)
require_once '../config.php';

$token = $_GET['token'] ?? '';
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $message = "Password and confirm password are required";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long";
    } else {
        // Verify token and update password
        $stmt = $conn->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_expires > NOW() AND is_verified = 1");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $update_stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user['id']);
            
            if ($update_stmt->execute()) {
                $success = true;
                $message = "Password reset successfully! You can now login with your new password.";
            } else {
                $message = "Failed to reset password. Please try again.";
            }
        } else {
            $message = "Invalid or expired reset token. Please request a new password reset.";
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Reset Password - Kenya EduHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth-animations.css">
    <style>
        /* Base */
        body {
            background: var(--card-bg, #f8f9fa);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
            color: #202124;
        }

        body::before,
        body::after {
            display: none !important;
        }

        html {
            background: var(--card-bg, #f8f9fa);
        }

        /* Card */
        .login-card {
            background: #ffffff;
            max-width: 420px;
            width: 100%;
            padding: 3rem 2.5rem 2.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
            border: 1px solid #e8eaed;
        }

        .login-card::before {
            display: none;
        }

        .login-card:hover {
            transform: none;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16), 0 3px 6px rgba(0, 0, 0, 0.23);
            background: #ffffff;
            border: 1px solid #e8eaed;
        }

        .login-card:hover::before {
            display: none;
        }

        .login-card h3 {
            font-weight: 700;
            color: #202124;
            margin-bottom: 1.75rem;
            text-align: center;
            text-shadow: none;
        }

        /* Form inputs */
        input.form-control {
            height: 48px;
            font-size: 1rem;
            border-radius: 25px;
            border: 1px solid #dadce0;
            background: #ffffff;
            color: #202124;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input.form-control::placeholder {
            color: #5f6368;
            opacity: 1;
        }

        input.form-control:-webkit-autofill,
        input.form-control:-webkit-autofill:hover,
        input.form-control:-webkit-autofill:focus {
            -webkit-text-fill-color: #202124;
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset;
            transition: background-color 5000s ease-in-out 0s;
        }

        input.form-control:focus {
            border-color: #FF6B35;
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }

        /* Button */
        button.btn-primary {
            width: 100%;
            height: 48px;
            font-weight: 700;
            font-size: 1.125rem;
            border-radius: 25px;
            background: #FF6B35;
            border: none;
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

        /* Alert styling */
        .alert {
            background-color: #000;
            border-radius: 0;
            padding: 0.9rem 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
            box-shadow: none;
            user-select: none;
            animation: none;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
            color: #202124;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
        }

        .auth-brand-logo .brand-text {
            line-height: 1;
        }

        .login-card h3 {
            color: #ffffff;
        }

        .login-card h3::first-letter {
            color: var(--primary-orange);
        }
    </style>
</head>
<body>
    <main class="login-card" role="main" aria-label="Password Reset Form">
        <div class="text-center mb-4">
            <div class="auth-brand-logo" aria-label="Kenya EduHub Logo">
                <?php require_once '../includes/logo.php'; ?>
            </div>
            <br>
            <h3>Reset Password</h3>
            <p style="color: #6c757d; margin-bottom: 1.5rem;">Enter your new password below.</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success" role="alert" aria-live="assertive">
                <p><?= $message ?></p>
            </div>
            <div class="text-center mt-4">
                <p class="small"><a href="login">Back to Login</a></p>
            </div>
        <?php else: ?>
            <?php if (!empty($message)): ?>
                <div class="alert alert-warning" role="alert" aria-live="assertive">
                    <p><?= $message ?></p>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-4">
                    <label for="password" class="visually-hidden">New Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter new password"
                        minlength="6"
                        required
                        autocomplete="new-password"
                    />
                </div>
                <div class="mb-4">
                    <label for="confirm_password" class="visually-hidden">Confirm Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control"
                        placeholder="Confirm new password"
                        minlength="6"
                        required
                        autocomplete="new-password"
                    />
                </div>
                <button type="submit" class="btn btn-primary" aria-label="Reset password">
                    <i class="fas fa-key me-2"></i>
                    Reset Password
                </button>
            </form>

            <div class="text-center mt-4">
                <p class="small">
                    <a href="login">Back to Login</a>
                    <span style="margin: 0 10px;">•</span>
                    <a href="forgot_password">Forgot Password?</a>
                </p>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <?php require_once '../includes/copywrite.php'; ?>
</body>
</html>
