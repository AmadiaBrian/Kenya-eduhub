<?php
// Session is already started by the router (auth/index.php)
require_once '../config.php';

$email = $_SESSION['pending_user_email'] ?? null;
$message = "";
$step = $email ? 2 : 1; // Step 1: Enter email, Step 2: Enter code

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && $step === 1) {
        // Step 1: User submits email
        $email = trim($_POST['email']);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $conn->prepare("SELECT id, is_verified FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if ($user['is_verified']) {
                    $message = "Account already verified.";
                } else {
                    $_SESSION['pending_user_email'] = $email;
                    $step = 2;
                    $message = "Enter the verification code sent to your email.";
                }
            } else {
                $message = "Email not found.";
            }
            $stmt->close();
        } else {
            $message = "Invalid email address.";
        }
    } elseif (isset($_POST['code']) && $step === 2) {
        // Step 2: User submits verification code
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
            $message = "Code expired. Please request a new code.";
        } elseif ($code === $correct_code) {
            $conn->query("UPDATE users SET is_verified = 1, verification_code = NULL WHERE email = '$email'");
            unset($_SESSION['pending_user_email']);
            $_SESSION['login_success'] = "✅ Your account has been verified. Please log in.";
            header("Location: login");
            exit();
        } else {
            $message = "Incorrect code.";
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
    <title>Verify Code - Kenya EduHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth-animations.css">
    <style>
        /* Base */
        body {
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Google Sans', 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #202124;
        }

        body::before,
        body::after {
            display: none !important;
        }

        html {
            background: #ffffff;
        }

        .login-card {
            width: 100%;
            max-width: 450px;
            padding: 48px 40px 36px;
            display: flex;
            flex-direction: column;
            align-items: center;
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

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-warning {
            background: #fef7e0;
            color: #b06000;
            border: 1px solid #fef7e0;
        }

        .alert-error {
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid #fce8e6;
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

        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px 24px;
            }
        }
    </style>
</head>
<body>
<main class="login-card" role="main" aria-label="Email Verification Form">
    <div class="auth-brand-logo" aria-label="Kenya EduHub Logo">
        <div class="logo-circle">
            <span style="color: #FF6B35; font-size: 28px;">K</span><span style="color: #008000; font-size: 24px;">E</span>
        </div>
        <span class="brand-text"><span style="color: #FF6B35;">Kenya</span> <span style="color: #008000;">EduHub</span></span>
    </div>
    <?php if ($step === 1): ?>
        <h3>Verify Email</h3>
        <?php if (!empty($message)): ?>
            <div class="alert alert-warning" role="alert" aria-live="assertive"><?= $message ?></div>
        <?php endif; ?>
        <form method="post" class="login-form">
            <div class="form-group">
                <label for="email">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="Enter your email"
                    required
                    autocomplete="email"
                />
            </div>
            <button type="submit" class="btn-primary" aria-label="Continue to verification code">
                Continue
            </button>
        </form>
    <?php else: ?>
        <h3>Enter Verification Code</h3>
        <?php if (!empty($message)): ?>
            <div class="alert alert-warning" role="alert" aria-live="assertive"><?= $message ?></div>
        <?php endif; ?>
        <form method="post" class="login-form">
            <div class="form-group">
                <label for="code">Verification code</label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    class="form-control"
                    placeholder="Enter 6-digit code"
                    maxlength="6"
                    required
                    autocomplete="one-time-code"
                />
            </div>
            <button type="submit" class="btn-primary" aria-label="Verify email address">
                Verify Code
            </button>
        </form>
        <div class="login-links">
            <a href="?step=1">Wrong email? Change email</a>
        </div>
    <?php endif; ?>
    
    <div class="login-links">
        <a href="login">Back to Login</a>
    </div>
</main>
</body>
</html>
