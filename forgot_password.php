<?php
session_start();
require_once 'config.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

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
<html>
<head>
    <title>Reset Password - Kenya EduHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow p-4" style="width: 100%; max-width: 480px;">
        <h4 class="mb-3 text-center">Forgot Password</h4>

        <?php if ($message): ?>
            <div class="alert alert-info text-center"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <form method="post">
                <input type="email" name="email" class="form-control mb-3" placeholder="Enter your email" required>
                <button class="btn btn-primary w-100">Send Verification Code</button>
            </form>

        <?php elseif ($step === 2): ?>
            <form method="post">
                <input type="text" name="code" class="form-control mb-3" placeholder="Enter 6-digit code" required>
                <button class="btn btn-success w-100">Verify Code</button>
            </form>

        <?php elseif ($step === 3): ?>
            <form method="post">
                <input type="password" name="new_password" class="form-control mb-2" placeholder="New password" required>
                <input type="password" name="confirm_password" class="form-control mb-3" placeholder="Confirm password" required>
                <button class="btn btn-success w-100">Reset Password</button>
            </form>
        <?php endif; ?>

        <div class="text-center mt-3">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>
