<?php
// Session is already started by the router (auth/index.php)
require_once '../config.php';

// Check if user came from registration
if (!isset($_SESSION['verification_email'])) {
    header("Location: register");
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
                header("Location: login");
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
    <meta name="theme-color" content="#FF6B35">
    <title>Verify Email - Kenya EduHub</title>
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

        .verify-card {
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

        .verify-card h3 {
            font-size: 24px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 8px;
            text-align: center;
        }

        .verify-form {
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
            text-align: center;
            letter-spacing: 0.1em;
            font-weight: 600;
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

        .email-display {
            background: #f8f9fa;
            border: 1px solid #dadce0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 24px;
            text-align: center;
            font-weight: 500;
            color: #5f6368;
        }

        .verify-links {
            text-align: center;
            margin-top: 24px;
        }

        .verify-links a {
            color: #FF6B35;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .verify-links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .verify-card {
                padding: 32px 24px 24px;
            }
        }
    </style>
</head>
<body>
    <main class="verify-card" role="main" aria-label="Email Verification Form">
        <div class="auth-brand-logo" aria-label="Kenya EduHub Logo">
            <div class="logo-circle">
                <span style="color: #FF6B35; font-size: 28px;">K</span><span style="color: #008000; font-size: 24px;">E</span>
            </div>
            <span class="brand-text"><span style="color: #FF6B35;">Kenya</span> <span style="color: #008000;">EduHub</span></span>
        </div>

        <h3>Verify Email</h3>

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

        <form method="POST" novalidate class="verify-form">
            <div class="form-group">
                <label for="verification_code">Enter 6-digit verification code</label>
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
                Verify Email
            </button>
        </form>

        <div class="verify-links">
            <a href="register">Register again</a>
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
