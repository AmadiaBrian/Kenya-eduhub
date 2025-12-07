<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['pending_user_email'])) {
    die("Session expired.");
}

$email = $_SESSION['pending_user_email'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);

    $stmt = $conn->prepare("SELECT verification_code, is_verified, code_expires_at FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($correct_code, $is_verified, $expires_at);
    $stmt->fetch();
    $stmt->close();

    if ($is_verified) {
        $message = "Account already verified.";
    } elseif (new DateTime() > new DateTime($expires_at)) {
        $message = "Code expired. Click Resend to get a new one.";
    } elseif ($code === $correct_code) {
        $conn->query("UPDATE users SET is_verified = 1, verification_code = NULL WHERE email = '$email'");
        unset($_SESSION['pending_user_email']);
        $_SESSION['login_success'] = "✅ Your account has been verified. Please log in.";
        header("Location: index.php");
        exit();
    } else {
        $message = "Incorrect code.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify Code</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">
<div class="card p-4 shadow" style="width: 100%; max-width: 400px;">
    <h4 class="mb-3">Enter Verification Code</h4>
    <?php if (!empty($message)): ?>
        <div class="alert alert-warning"><?= $message ?></div>
    <?php elseif (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <input type="text" name="code" class="form-control" placeholder="Enter 6-digit code" maxlength="6" required>
        </div>
        <button class="btn btn-primary w-100">Verify</button>
    </form>
</div>
</body>
</html>
