<?php
// Session is already started by the router (auth/index.php)
require_once '../config.php';
require_once '../includes/helpers.php';
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
    // Get SMTP settings from database
    $smtp_settings = getSMTPSettings();
    
    if (!$smtp_settings) {
        error_log("SMTP settings not configured in database");
        return false;
    }
    
    $smtp_host = $smtp_settings['smtp_host'];
    $smtp_port = $smtp_settings['smtp_port'];
    $smtp_username = $smtp_settings['smtp_username'];
    $smtp_password = $smtp_settings['smtp_password'];
    $email_from = $smtp_settings['email_from'];
    $encryption = $smtp_settings['encryption'];
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = $encryption;
        $mail->Port = $smtp_port;

        $mail->setFrom($email_from, 'Kenya EduHub');
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
        error_log("Email sending failed: " . $e->getMessage());
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
            $message = "Password reset successfully. <a href='login'>Login</a>";
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
    <meta name="theme-color" content="#FF6B35">
    <title>Forgot Password - Kenya EduHub</title>
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

        .login-card h3 {
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

        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px 24px;
            }
        }
    </style>
</head>
<body>
    <main class="login-card" role="main" aria-label="Password Reset Form">
        <div class="auth-brand-logo" aria-label="Kenya EduHub Logo">
            <div class="logo-circle">
                <span style="color: #FF6B35; font-size: 28px;">K</span><span style="color: #008000; font-size: 24px;">E</span>
            </div>
            <span class="brand-text"><span style="color: #FF6B35;">Kenya</span> <span style="color: #008000;">EduHub</span></span>
        </div>

        <h3>Forgot Password?</h3>

        <?php if ($message): ?>
            <div class="alert-success" role="alert" aria-live="assertive">
                <p><?= $message ?></p>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <form method="post" class="login-form">
                <div class="form-group">
                    <label for="email">Email address</label>
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
                <button type="submit" class="btn btn-primary" aria-label="Send verification code">
                    Send Code
                </button>
            </form>

        <?php elseif ($step === 2): ?>
            <form method="post" class="login-form">
                <div class="form-group">
                    <label for="code">Verification Code</label>
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
                    Verify Code
                </button>
            </form>

        <?php elseif ($step === 3): ?>
            <form method="post" class="login-form">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        class="form-control"
                        placeholder="New password"
                        required
                    />
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
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
                    Reset Password
                </button>
            </form>
        <?php endif; ?>

        <div class="login-links">
            <a href="login">Back to Login</a>
        </div>
    </main>
</body>
</html>
