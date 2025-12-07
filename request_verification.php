<?php
session_start();
require_once 'config.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

function sendVerificationCode($email, $name, $code) {
    $mail = new PHPMailer(true);
    try {
          $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'otienobrian029@gmail.com';
            $mail->Password = 'dwuunoftzkodeome';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

        $mail->setFrom('yourgmail@gmail.com', 'Kenya Eduhub');
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Your Kenya Eduhub Verification Code';
        $mail->Body = "<p>Hello <strong>$name</strong>,</p>
                       <p>Your verification code is:</p>
                       <h2>$code</h2>
                       <p>This code will expire in 10 minutes.</p>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    $stmt = $conn->prepare("SELECT name, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $message = "❌ No account found with this email.";
    } else {
        $stmt->bind_result($name, $is_verified);
        $stmt->fetch();

        if ($is_verified) {
            $message = "✅ Your account is already verified. Please login.";
        } else {
            $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $update = $conn->prepare("UPDATE users SET verification_code = ?, code_expires_at = ? WHERE email = ?");
            $update->bind_param("sss", $code, $expires, $email);
            if ($update->execute() && sendVerificationCode($email, $name, $code)) {
                $_SESSION['pending_user_email'] = $email;
                $_SESSION['success_message'] = "Verification code sent to $email.";
                header("Location: verify_code.php");
                exit();
            } else {
                $message = "❌ Failed to send code. Please try again.";
            }
        }
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow" style="max-width: 420px; width: 100%;">
        <h4 class="mb-3 text-center">Request Email Verification</h4>
        <?php if (!empty($message)): ?>
            <div class="alert alert-info text-center"><?= $message ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required />
            </div>
            <div class="d-grid">
                <button class="btn btn-primary">Send Code</button>
            </div>
        </form>
        <div class="text-center mt-3">
            <a href="login.php" class="text-decoration-none">← Back to Login</a>
        </div>
    </div>
</body>
</html>
