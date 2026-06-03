<?php
session_start();
require_once '../config.php';
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

// Initialize
$message = '';
$step = $_SESSION['step'] ?? 1;
$email = $_SESSION['reset_email'] ?? null;

// Function to send email
function sendResetCode($email, $code) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'otienobrian029@gmail.com'; // Your Gmail
        $mail->Password   = 'dwuunoftzkodeome';         // App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('otienobrian029@gmail.com', 'Kenya EduHub');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Code - Kenya EduHub';
        $mail->Body = "
            <p>You requested a password reset.</p>
            <p>Your verification code is: <strong>$code</strong></p>
            <p>Code expires in 10 minutes.</p>
        ";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Step 1: User submits email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && $step === 1) {
    $email = trim($_POST['email']);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $update = $conn->prepare("UPDATE users SET reset_code = ?, reset_expires_at = ? WHERE email = ?");
            $update->bind_param("sss", $code, $expires, $email);
            if ($update->execute() && sendResetCode($email, $code)) {
                $_SESSION['reset_email'] = $email;
                $_SESSION['step'] = 2;
                $step = 2;
                $message = "Verification code sent to your email.";
            } else {
                $message = "Failed to send code. Try again.";
            }
        } else {
            $message = "Email not found.";
        }
        $stmt->close();
    } else {
        $message = "Invalid email address.";
    }
}

// Step 2: Verify code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code']) && $step === 2) {
    $code = trim($_POST['code']);
    $stmt = $conn->prepare("SELECT reset_expires_at FROM users WHERE email = ? AND reset_code = ?");
    $stmt->bind_param("ss", $email, $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        if (strtotime($data['reset_expires_at']) > time()) {
            $_SESSION['step'] = 3;
            $step = 3;
            $message = "Code verified. Please enter your new password.";
        } else {
            $message = "Code expired.";
        }
    } else {
        $message = "Invalid code.";
    }
    $stmt->close();
}

// Step 3: Reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password']) && $step === 3) {
    $pass = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($pass !== $confirm) {
        $message = "Passwords do not match.";
    } elseif (strlen($pass) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_expires_at = NULL WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);
        if ($stmt->execute()) {
            $message = "Password reset successfully. <a href='index.php'>Login</a>";
            session_unset();
            session_destroy();
        } else {
            $message = "Error updating password.";
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
    <title>Forgot Password - Kenya EduHub</title>
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
            color: #856404;
            border: 1px solid #856404;
        }

        .alert-info {
            background-color: #000;
            color: #0dcaf0;
            border: 1px solid #0dcaf0;
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
    </style>
</head>
<body>
    <main class="login-card" role="main" aria-label="Password Reset Form">
        <div class="text-center mb-4">
            <img src="../assets/favicon.ico" alt="Kenya EduHub Logo" style="height: 60px; margin-bottom: 1rem;">
            <h3>Forgot Password?</h3>
            <p style="color: #6c757d; margin-bottom: 1.5rem;">Enter your email address and we'll send you a link to reset your password.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info" role="alert" aria-live="assertive">
                <p><?= $message ?></p>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <form method="post">
                <div class="mb-4">
                    <label for="email" class="visually-hidden">Email address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="Enter your email address"
                        value="<?= htmlspecialchars($email) ?>"
                        required
                        autocomplete="email"
                    />
                </div>
                <button type="submit" class="btn btn-primary" aria-label="Resend verification code">
                    <i class="fas fa-paper-plane me-2"></i>
                    Resend Code
                </button>
            </form>

        <?php elseif ($step === 2): ?>
            <form method="post">
                <div class="mb-4">
                    <label for="code" class="visually-hidden">Verification Code</label>
                    <input
                        type="text"
                        id="code"
                        name="code"
                        class="form-control"
                        placeholder="Enter 6-digit code"
                        required
                    />
                </div>
                <button type="submit" class="btn btn-primary" aria-label="Verify Code">
                    <i class="fas fa-check me-2"></i>
                    Verify Code
                </button>
            </form>

        <?php elseif ($step === 3): ?>
            <form method="post">
                <div class="mb-4">
                    <label for="new_password" class="visually-hidden">New Password</label>
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        class="form-control"
                        placeholder="New password"
                        required
                    />
                </div>
                <div class="mb-4">
                    <label for="confirm_password" class="visually-hidden">Confirm Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control"
                        placeholder="Confirm password"
                        required
                    />
                </div>
                <button type="submit" class="btn btn-primary" aria-label="Reset Password">
                    <i class="fas fa-lock me-2"></i>
                    Reset Password
                </button>
            </form>
        <?php endif; ?>

        <div class="text-center mt-4">
            <p class="small"><a href="login.php">Back to Login</a></p>
        </div>
    </main>
</body>
</html>
