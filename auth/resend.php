<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session is already started by the router (auth/index.php)
require_once '../config.php';
require_once '../includes/helpers.php';

// Load PHPMailer
require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!validateEmailLite($email)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if user exists and is not verified
        $stmt = $conn->prepare("SELECT id, name, email, phone, is_verified FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $errors[] = "No account found with this email address";
        } else {
            $user = $result->fetch_assoc();

            if ($user['is_verified'] == 1) {
                $errors[] = "This account is already verified. You can login directly.";
            } else {
                // Generate new verification code
                $verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $code_expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                // Update user with new verification code
                $update_stmt = $conn->prepare("UPDATE users SET verification_code = ?, code_expires_at = ? WHERE id = ?");
                $update_stmt->bind_param("ssi", $verification_code, $code_expires_at, $user['id']);
                $update_stmt->execute();

                // Send verification email using PHPMailer
                $mail = new PHPMailer(true);
                $email_sent = false;

                try {
                    // Get SMTP settings from database
                    $smtp_settings = getSMTPSettings();

                    if (!$smtp_settings) {
                        throw new Exception("SMTP settings not configured in database");
                    }

                    $smtp_host = $smtp_settings['smtp_host'];
                    $smtp_port = $smtp_settings['smtp_port'];
                    $smtp_username = $smtp_settings['smtp_username'];
                    $smtp_password = $smtp_settings['smtp_password'];
                    $email_from = $smtp_settings['email_from'];
                    $encryption = $smtp_settings['encryption'];

                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = $smtp_host;
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtp_username;
                    $mail->Password = $smtp_password;
                    $mail->SMTPSecure = $encryption;
                    $mail->Port = $smtp_port;

                    // Recipients
                    $mail->setFrom($email_from, 'Kenya EduHub');
                    $mail->addAddress($user['email']);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Verify Your Account - Kenya EduHub';
                    $mail->Body = "
                        <p>Hello " . htmlspecialchars($user['name']) . ",</p>
                        <p>Your verification code is: <strong>$verification_code</strong></p>
                        <p>Please enter this code to verify your account.</p>
                        <p>This code will expire in 10 minutes.</p>
                        <p>If you did not request this code, please ignore this email.</p>
                        <p>Regards,<br>Kenya EduHub Team</p>
                    ";

                    $mail->send();
                    $email_sent = true;
                    error_log("Verification email sent to: " . $user['email']);
                } catch (Exception $e) {
                    error_log("Email error: " . $e->getMessage());
                }

                // Set session variables for verification page
                $_SESSION['verification_email'] = $user['email'];
                $_SESSION['verification_phone'] = $user['phone'] ?? null;
                $_SESSION['last_resend_time'] = time();
                $_SESSION['resend_attempt_count'] = 0;

                if ($email_sent) {
                    $success = "Verification code has been sent to your email. Redirecting to verification page...";

                    // Redirect to verification page after 2 seconds
                    header("refresh:2; url=verify");
                } else {
                    $errors[] = "Failed to send verification code. Please check your email settings or try again later.";
                }
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
    <meta name="theme-color" content="#FF6B35">
    <title>Resend Verification Code - Kenya EduHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="../assets/css/auth-animations.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--card-bg, #f8f9fa);
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

        .btn-primary:disabled {
            background: #ccc;
            border-color: #ccc;
            cursor: not-allowed;
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

        .login-links {
            text-align: center;
            margin-top: 24px;
        }

        .material-symbols-outlined {
            font-size: 24px;
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
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

            .login-card h1 {
                font-size: 20px;
            }

            .form-control {
                font-size: 16px;
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

            .login-card h1 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body class="login-page">
    <main class="login-card" role="main" aria-label="Resend Verification Code Form">
        <div class="auth-brand-logo" aria-label="Kenya EduHub Logo">
            <?php require_once '../includes/logo.php'; ?>
        </div>

        <br>

        <h1>Resend Verification Code</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert-error" role="alert" aria-live="assertive">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert-success" role="alert">
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="resend" class="login-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required autocomplete="email" placeholder="Enter your email">
            </div>

            <button type="submit" class="btn-primary">
                Send Verification Code
            </button>
        </form>

        <div class="login-links">
            <div style="margin-top: 16px;">
                <p style="font-size: 13px; color: #5f6368; margin-bottom: 8px;">Already have a code?</p>
                <a href="verify" style="color: #FF6B35; text-decoration: none;">Verify Account</a>
            </div>
            <br>
            <div style="margin-top: 16px;">
                <p style="font-size: 13px; color: #5f6368; margin-bottom: 8px;">Remember your password?</p>
                <a href="login" style="color: #FF6B35; text-decoration: none;">Login</a>
            </div>
        </div>
    </main>
</body>
</html>
