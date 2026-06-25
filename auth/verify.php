<?php
session_start();
require_once '../config.php';

// Check if user came from registration
if (!isset($_SESSION['verification_email'])) {
    header("Location: register.php");
    exit();
}

// Store email in local variable in case session gets cleared
$email = $_SESSION['verification_email'];
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verification_code = $_POST['verification_code'] ?? '';
    
    if (empty($verification_code)) {
        $errors[] = "Verification code is required";
    } elseif (strlen($verification_code) !== 6) {
        $errors[] = "Verification code must be 6 digits";
    } else {
        // Verify the code
        $stmt = $conn->prepare("SELECT id, name, email, code_expires_at FROM users WHERE email = ? AND verification_code = ? AND is_verified = 0");
        $stmt->bind_param("ss", $email, $verification_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if code is expired
            if (strtotime($user['code_expires_at']) < time()) {
                $errors[] = "Verification code has expired. Please request a new code.";
                // Don't clear session on expired code - allow user to try again
            } else {
                // Mark user as verified
                $update_stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_code = NULL, code_expires_at = NULL WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                
                // Clear session and redirect to login
                unset($_SESSION['verification_email']);
                $_SESSION['success'] = "Email verified successfully! You can now login.";
                header("Location: login.php");
                exit();
            }
        } else {
            $errors[] = "Invalid verification code. Please check your email and try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Kenya EduHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth-animations.css">
    <style>
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

        .verify-card {
            background: #000000;
            max-width: 480px;
            width: 100%;
            padding: 3rem 2.5rem 2.5rem;
            border-radius: 0;
            box-shadow: none;
            border: none;
            animation: none;
            position: relative;
            overflow: hidden;
            transition: none;
        }

        .verify-card::before {
            display: none;
        }

        .verify-card:hover {
            transform: none;
            box-shadow: none;
            background: #000000;
            border: none;
        }

        .verify-card h3 {
            font-weight: 700;
            color: #666;
            margin-bottom: 1.75rem;
            text-align: center;
            text-shadow: none;
        }

        .email-display {
            background: rgba(78, 161, 255, 0.1);
            border: 1px solid rgba(78, 161, 255, 0.3);
            border-radius: 0.625rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
            color: #1e3c72;
        }

        input.form-control {
            height: 48px;
            font-size: 1.25rem;
            border-radius: 0;
            border: 2px solid #fff;
            background: #000;
            color: #fff !important;
            transition: none;
            text-align: center;
            letter-spacing: 0.1em;
            font-weight: 600;
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

        .alert-error {
            background-color: #000;
            color: #ff0000;
            border: 1px solid #ff0000;
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
            border-radius: 0;
            padding: 0.9rem 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
            box-shadow: none;
            user-select: none;
            animation: none;
        }

        .resend-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .resend-link a {
            color: #4ea1ff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.25s ease;
        }

        .resend-link a:hover,
        .resend-link a:focus-visible {
            color: #1e3c72;
            text-decoration: underline;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        @media (max-width: 480px) {
            .verify-card {
                padding: 2rem 1.5rem 2rem;
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

        .verify-card h3 {
            color: #ffffff;
        }

        .verify-card h3::first-letter {
            color: var(--primary-orange);
        }
    </style>
</head>
<body>
    <main class="verify-card" role="main" aria-label="Email Verification Form">
        <div class="text-center mb-4">
            <div class="auth-brand-logo" aria-label="Kenya EduHub Logo">
                <div style="width: 50px; height: 50px; background: var(--primary-gold); border: 3px solid var(--primary-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 2px;">
                    <span style="font-weight: bold; font-size: 24px;">
                        <span style="color: var(--primary-orange); font-size: 28px;">K</span><span style="color: #008000; font-size: 24px;">E</span>
                    </span>
                </div>
                <span class="brand-text"><span style="color: var(--primary-orange);">Kenya</span> <span style="color: #008000;">EduHub</span></span>
            </div>
            <h3>Verify Email</h3>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success" role="alert">
                <p><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert-error" role="alert" aria-live="assertive">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="email-display">
            <i class="fas fa-envelope me-2"></i>
            <?php echo htmlspecialchars($email); ?>
        </div>

        <form method="POST" novalidate>
            <div class="mb-4">
                <label for="verification_code" class="form-label">Enter 6-digit verification code</label>
                <input
                    type="text"
                    id="verification_code"
                    name="verification_code"
                    class="form-control"
                    placeholder="000000"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    required
                    autocomplete="one-time-code"
                    autofocus
                />
            </div>

            <button type="submit" class="btn btn-primary" aria-label="Verify email address">
                <i class="fas fa-check-circle me-2"></i>
                Verify Email
            </button>
        </form>

        <div class="resend-link">
            <p>Didn't receive the code?</p>
            <button type="button" class="btn btn-outline-primary" onclick="resendCode()" style="width: auto; padding: 8px 16px; margin-right: 10px;">
                <i class="fas fa-redo me-2"></i>
                Resend Code
            </button>
            <a href="register.php">Register again</a>
        </div>
    </main>

    <script>
        // Auto-focus and select all on input
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.getElementById('verification_code');
            codeInput.focus();
            codeInput.select();
            
            // Only allow numbers
            codeInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
            
            // Auto-submit when 6 digits entered
            codeInput.addEventListener('input', function() {
                if (this.value.length === 6) {
                    // Small delay for better UX
                    setTimeout(() => {
                        document.querySelector('form').submit();
                    }, 500);
                }
            });
        });

        // Resend verification code
        function resendCode() {
            const email = '<?php echo htmlspecialchars($email); ?>';
            
            // Show loading state
            const resendBtn = event.target;
            const originalText = resendBtn.innerHTML;
            resendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            resendBtn.disabled = true;
            
            // Call API to resend code
            fetch('../api/resend_verification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Origin': 'http://localhost'
                },
                body: JSON.stringify({
                    email: email
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    const successDiv = document.createElement('div');
                    successDiv.className = 'alert-success';
                    successDiv.role = 'alert';
                    successDiv.innerHTML = `<p>${data.message}</p>`;
                    
                    // Insert before the form
                    const form = document.querySelector('form');
                    form.parentNode.insertBefore(successDiv, form);
                    
                    // Remove after 5 seconds
                    setTimeout(() => {
                        successDiv.remove();
                    }, 5000);
                } else {
                    // Show error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'alert-error';
                    errorDiv.role = 'alert';
                    errorDiv.setAttribute('aria-live', 'assertive');
                    errorDiv.innerHTML = `<p>${data.message}</p>`;
                    
                    // Insert before the form
                    const form = document.querySelector('form');
                    form.parentNode.insertBefore(errorDiv, form);
                    
                    // Remove after 5 seconds
                    setTimeout(() => {
                        errorDiv.remove();
                    }, 5000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to resend verification code. Please try again.');
            })
            .finally(() => {
                // Restore button state
                resendBtn.innerHTML = originalText;
                resendBtn.disabled = false;
            });
        }
    </script>
</body>
</html>
