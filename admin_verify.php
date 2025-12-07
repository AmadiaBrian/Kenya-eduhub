<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_verification'])) {
    $_SESSION['error'] = 'Verification session expired. Please register again.';
    header('Location: admin_register.php');
    exit();
}

$verification_data = $_SESSION['admin_verification'];

// Check if verification code is expired
if (strtotime($verification_data['expires']) < time()) {
    unset($_SESSION['admin_verification']);
    $_SESSION['error'] = 'Verification code has expired. Please register again.';
    header('Location: admin_register.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $entered_code = $_POST['verification_code'];
    
    if ($entered_code === $verification_data['code']) {
        // Verification successful, create admin account
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, is_verified) VALUES (?, ?, ?, 'admin', 1)");
        $stmt->bind_param("sss", $verification_data['name'], $verification_data['email'], $verification_data['password']);
        
        if ($stmt->execute()) {
            // Clear verification session
            unset($_SESSION['admin_verification']);
            
            // Set success message
            $_SESSION['success'] = 'Admin account created successfully! You can now login.';
            
            // Redirect to admin login
            header('Location: admin_login.php');
            exit();
        } else {
            $error = 'Failed to create admin account. Please try again.';
        }
    } else {
        $error = 'Invalid verification code. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Admin Account - Kenya Eduhub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #153b50 0%, #3f87a6 100%);
        }
        .verification-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
        }
        .verification-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .verification-logo h2 {
            color: #153b50;
            font-weight: 700;
        }
        .form-control {
            height: 45px;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 1.2rem;
            letter-spacing: 5px;
        }
        .btn-verify {
            background: #153b50;
            border: none;
            padding: 10px 20px;
            font-weight: 600;
            width: 100%;
            height: 45px;
        }
        .btn-verify:hover {
            background: #0d2b3a;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="verification-card">
                    <div class="verification-logo">
                        <h2>Verify Admin Account</h2>
                        <p class="text-muted">Enter the verification code sent to <?= htmlspecialchars($verification_data['email']) ?></p>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <input type="text" 
                                   name="verification_code" 
                                   class="form-control form-control-lg text-center" 
                                   placeholder="Enter 6-digit code" 
                                   maxlength="6" 
                                   pattern="\d{6}" 
                                   required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="verify" class="btn btn-primary btn-verify">Verify Account</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p>Didn't receive a code? <a href="#" onclick="resendCode()">Resend Code</a></p>
                        <a href="admin_register.php" class="text-decoration-none">← Back to Registration</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function resendCode() {
        // You can implement AJAX to resend the code
        alert('A new verification code has been sent to your email.');
        // For now, just reload the page
        window.location.href = window.location.href;
    }
    </script>
</body>
</html>
