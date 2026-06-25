<?php
session_start();
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
    <title>Reset Password - Kenya EduHub</title>
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

        .login-card:hover::before {
            display: none;
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
            background-color: #000;
            color: #0f5132;
            border: 1px solid #0f5132;
        }

        .alert-warning {
            background-color: #000;
            color: #ff0000;
            border: 1px solid #ff0000;
        }

        .alert-error {
            background-color: #000;
            color: #ff0000;
            border: 1px solid #ff0000;
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
            color: white;
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
                <div style="width: 50px; height: 50px; background: var(--primary-gold); border: 3px solid var(--primary-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 2px;">
                    <span style="font-weight: bold; font-size: 24px;">
                        <span style="color: var(--primary-orange); font-size: 28px;">K</span><span style="color: #008000; font-size: 24px;">E</span>
                    </span>
                </div>
                <span class="brand-text"><span style="color: var(--primary-orange);">Kenya</span> <span style="color: #008000;">EduHub</span></span>
            </div>
            <h3>Reset Password</h3>
            <p style="color: #6c757d; margin-bottom: 1.5rem;">Enter your new password below.</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success" role="alert" aria-live="assertive">
                <p><?= $message ?></p>
            </div>
            <div class="text-center mt-4">
                <p class="small"><a href="login.php">Back to Login</a></p>
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
                    <a href="login.php">Back to Login</a>
                    <span style="margin: 0 10px;">•</span>
                    <a href="forgot_password.php">Forgot Password?</a>
                </p>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
