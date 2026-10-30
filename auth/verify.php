<?php
// Session is already started by the router (auth/index.php)
require_once '../config.php';

// Check if user came from registration
if (!isset($_SESSION['verification_email']) && !isset($_SESSION['verification_phone'])) {
    header("Location: register");
    exit();
}

// Store email/phone in local variable in case session gets cleared
$email = $_SESSION['verification_email'] ?? null;
$phone = $_SESSION['verification_phone'] ?? null;
$name = '';
$errors = [];
$success = '';

// Fetch user details if email or phone is available
if ($email || $phone) {
    if ($email) {
        $stmt = $conn->prepare("SELECT name, phone FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $name = $user['name'];
            $phone = $user['phone'] ?? $phone;
        }
    } elseif ($phone) {
        $stmt = $conn->prepare("SELECT name, email FROM users WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $name = $user['name'];
            $email = $user['email'] ?? $email;
        }
    }
}

// Check resend cooldown based on attempt count
$can_resend = true;
$cooldown_remaining = 0;
$resend_attempt_count = $_SESSION['resend_attempt_count'] ?? 0;

// Initialize resend attempt count if not set
if (!isset($_SESSION['resend_attempt_count'])) {
    $_SESSION['resend_attempt_count'] = 0;
}

if (isset($_SESSION['last_resend_time'])) {
    $time_since_resend = time() - $_SESSION['last_resend_time'];

    // Determine cooldown based on attempt count
    if ($resend_attempt_count == 0) {
        // First resend - no cooldown
        $can_resend = true;
        $cooldown_remaining = 0;
    } elseif ($resend_attempt_count == 1) {
        // Second resend - 2 minutes (120 seconds)
        $cooldown_period = 120;
        if ($time_since_resend < $cooldown_period) {
            $can_resend = false;
            $cooldown_remaining = $cooldown_period - $time_since_resend;
        }
    } else {
        // Third and subsequent resends - 2 hours (7200 seconds)
        $cooldown_period = 7200;
        if ($time_since_resend < $cooldown_period) {
            $can_resend = false;
            $cooldown_remaining = $cooldown_period - $time_since_resend;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verification_code = $_POST['verification_code'] ?? '';
    error_log("Verification attempt - Entered code: $verification_code");

    if (empty($verification_code)) {
        $errors[] = "Verification code is required";
    } elseif (strlen($verification_code) !== 6) {
        $errors[] = "Verification code must be 6 digits";
    } else {
        // Verify the code - check by email first, then by phone
        $user = null;

        if ($email) {
            $stmt = $conn->prepare("SELECT id, name, email, code_expires_at FROM users WHERE email = ? AND verification_code = ? AND is_verified = 0");
            $stmt->bind_param("ss", $email, $verification_code);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                error_log("User found by email: {$user['email']}");
            }
        }

        // If not found by email, try phone
        if (!$user && $phone) {
            $stmt = $conn->prepare("SELECT id, name, email, code_expires_at FROM users WHERE phone = ? AND verification_code = ? AND is_verified = 0");
            $stmt->bind_param("ss", $phone, $verification_code);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                error_log("User found by phone: {$user['phone']}");
            }
        }

        if (!$user) {
            error_log("No user found with code: $verification_code for email: $email or phone: $phone");
            $errors[] = "Invalid verification code. Please check your email or SMS and try again.";
        } else {
            // Check if code is expired
            if (strtotime($user['code_expires_at']) < time()) {
                error_log("Code expired for user ID: {$user['id']}");
                $errors[] = "Verification code has expired. Please request a new code.";
                // Don't clear session on expired code - allow user to try again
            } else {
                error_log("Code valid for user ID: {$user['id']}, marking as verified");
                // Mark user as verified
                $update_stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_code = NULL, code_expires_at = NULL WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();

                // Clear session and redirect to login
                unset($_SESSION['verification_email']);
                unset($_SESSION['verification_phone']);
                unset($_SESSION['last_resend_time']);
                unset($_SESSION['resend_attempt_count']);
                $_SESSION['success'] = "Verification successful! You can now login.";
                header("Location: login");
                exit();
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
    <meta property="og:image" content="https://kenyaeduhub.kesug.com/assets/favicon.ico">
    <link rel="icon" type="image/x-icon" href="https://kenyaeduhub.kesug.com/assets/favicon.ico" />
    <link rel="apple-touch-icon" href="https://kenyaeduhub.kesug.com/assets/favicon.ico">
    <title>Verify Account - Kenya EduHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .material-symbols-outlined {
            font-size: 24px;
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        body {
            background: #F0F4F9;
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
            margin-top: -10px;
        }

        .verify-card h1 {
            font-size: 24px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 8px;
            text-align: center;
        }

        .welcome-section {
            background: #F0F4F9;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            text-align: center;
        }

        .welcome-text {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
        }

        .user-details {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 16px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            color: #5f6368;
        }

        .detail-icon {
            font-size: 18px;
            color: #FF6B35;
        }

        .detail-text {
            font-weight: 500;
        }

        .verify-instruction {
            font-size: 13px;
            color: #5f6368;
            margin-top: 12px;
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
            background: #F0F4F9;
        }

        .form-control:focus {
            border-color: #FF6B35;
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
            background: #F0F4F9;
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

        /* Responsive - Mobile */
        @media (max-width: 768px) {
            .verify-card {
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

            .verify-card h1 {
                font-size: 20px;
            }

            .welcome-section {
                padding: 16px;
            }

            .welcome-text {
                font-size: 16px;
            }

            .user-details {
                gap: 10px;
            }

            .detail-item {
                font-size: 13px;
            }

            .detail-icon {
                font-size: 16px;
            }

            .verify-instruction {
                font-size: 12px;
            }

            .form-control {
                font-size: 16px;
            }

            .btn-primary {
                font-size: 14px;
                padding: 12px 20px;
            }

            .verify-links {
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .verify-card {
                padding: 24px 20px 20px;
            }

            .auth-brand-logo {
                font-size: 1.2rem;
            }

            .auth-brand-logo .logo-circle {
                width: 40px;
                height: 40px;
            }

            .verify-card h1 {
                font-size: 18px;
            }

            .welcome-section {
                padding: 12px;
            }

            .welcome-text {
                font-size: 15px;
            }

            .detail-item {
                font-size: 12px;
                gap: 6px;
            }

            .detail-icon {
                font-size: 14px;
            }
        }

        /* Responsive - Mobile */
        @media (max-width: 768px) {
            .verify-card {
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

            .verify-card h1 {
                font-size: 20px;
            }

            .form-control {
                font-size: 16px; /* Prevent zoom on iOS */
            }

            .btn-primary {
                font-size: 14px;
                padding: 12px 20px;
            }

            .verify-links {
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .verify-card {
                padding: 24px 20px 20px;
            }

            .auth-brand-logo {
                font-size: 1.2rem;
            }

            .auth-brand-logo .logo-circle {
                width: 40px;
                height: 40px;
            }

            .verify-card h1 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body class="login-page">
    <main class="verify-card" role="main" aria-label="Account Verification Form">
        <div class="auth-brand-logo" aria-label="Kenya EduHub Logo">
            <?php require_once '../includes/logo.php'; ?>
        </div>

        <h1>Verify Account</h1>

        <div class="welcome-section">
            <p class="welcome-text">Welcome, <?php echo htmlspecialchars($name); ?>!</p>
            <div class="user-details">
                <div class="detail-item">
                    <span class="material-symbols-outlined detail-icon">mail</span>
                    <span class="detail-text"><?php echo htmlspecialchars($email); ?></span>
                </div>
                <?php if ($phone): ?>
                <div class="detail-item">
                    <span class="material-symbols-outlined detail-icon">smartphone</span>
                    <span class="detail-text"><?php echo htmlspecialchars($phone); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <p class="verify-instruction">Please verify your account to continue</p>
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

            <button type="submit" class="btn btn-primary" aria-label="Verify account">
                Verify Account
            </button>
        </form>

        <div class="verify-links">
            <a href="register">Register again</a>
            <br><br>
            <div style="margin-top: 16px;">
                <p style="font-size: 13px; color: #5f6368; margin-bottom: 8px;">Need to enter your email again?</p>
                <a href="resend" aria-label="Enter email to resend code" style="color: #FF6B35; text-decoration: none;">Resend Code</a>
            </div>
            <br><br>
            <div style="margin-top: 16px;">
                <p style="font-size: 13px; color: #5f6368; margin-bottom: 8px;">Didn't receive the code?</p>
                <?php if ($phone): ?>
                    <a href="#" id="resendSmsLink" onclick="resendCode('sms', event)" <?php echo !$can_resend ? 'style="color: #9aa0a6; pointer-events: none;"' : 'style="color: #FF6B35; text-decoration: none;"'; ?>>Resend via SMS</a>
                <?php else: ?>
                    <span style="color: #5f6368; font-size: 13px;">No phone number provided for SMS resend</span>
                <?php endif; ?>
                <?php if (!$can_resend && $phone): ?>
                    <?php
                    // Format cooldown time for display
                    $time_display = '';
                    if ($cooldown_remaining >= 3600) {
                        $hours = floor($cooldown_remaining / 3600);
                        $minutes = floor(($cooldown_remaining % 3600) / 60);
                        $time_display = $hours . ' hour' . ($hours > 1 ? 's' : '');
                        if ($minutes > 0) {
                            $time_display .= ' ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
                        }
                    } else {
                        $time_display = $cooldown_remaining . ' second' . ($cooldown_remaining != 1 ? 's' : '');
                    }
                    ?>
                <?php endif; ?>
            </div>
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
                    }, 200);
                }
            });
        });

        // Resend verification code
        function resendCode(method, e) {
            e.preventDefault();
            const email = '<?php echo htmlspecialchars($email); ?>';
            const phone = '<?php echo htmlspecialchars($phone); ?>';
            
            // Show loading state
            const resendBtn = e.target;
            const originalText = resendBtn.innerHTML;
            resendBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; animation: spin 1s linear infinite;">refresh</span> Sending...';
            resendBtn.disabled = true;
            resendBtn.style.pointerEvents = 'none';
            
            // Prepare request data
            const requestData = {
                method: method
            };
            if (email) {
                requestData.email = email;
            }
            if (phone) {
                requestData.phone = phone;
            }
            
            // Call API to resend code
            fetch('../api/resend_verification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Origin': 'http://localhost'
                },
                body: JSON.stringify(requestData)
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
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

                    // Start cooldown timer
                    console.log("Starting cooldown timer. attempt_count:", data.attempt_count);
                    console.log("SMS link element:", document.getElementById('resendSmsLink'));

                    // Calculate cooldown period based on attempt_count
                    let cooldownPeriod;
                    if (data.attempt_count === 0) {
                        cooldownPeriod = 0; // First resend - no cooldown
                    } else if (data.attempt_count === 1) {
                        cooldownPeriod = 120; // Second resend - 2 minutes
                    } else {
                        cooldownPeriod = 7200; // Third and subsequent - 2 hours
                    }

                    startCooldownTimer(cooldownPeriod, data.attempt_count);
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
                // Show error message in form instead of alert
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert-error';
                errorDiv.role = 'alert';
                errorDiv.setAttribute('aria-live', 'assertive');
                errorDiv.innerHTML = `<p>Failed to resend verification code: ${error.message || 'Please try again.'}</p>`;

                // Insert before the form
                const form = document.querySelector('form');
                form.parentNode.insertBefore(errorDiv, form);

                // Remove after 5 seconds
                setTimeout(() => {
                    errorDiv.remove();
                }, 5000);
            })
            .finally(() => {
                // Restore button state
                resendBtn.innerHTML = originalText;
                resendBtn.disabled = false;
            });
        }

        // Cooldown timer function
        function startCooldownTimer(initialRemaining, attemptCount = 1) {
            console.log("startCooldownTimer called. initialRemaining:", initialRemaining, "attemptCount:", attemptCount);
            let remaining = initialRemaining;

            // Determine cooldown period based on attempt count
            let cooldownPeriod;
            if (attemptCount === 0) {
                cooldownPeriod = 0; // First resend - no cooldown
                console.log("No cooldown (first resend)");
            } else if (attemptCount === 1) {
                cooldownPeriod = 120; // Second resend - 2 minutes
                console.log("2-minute cooldown");
            } else {
                cooldownPeriod = 7200; // Third and subsequent - 2 hours
                console.log("2-hour cooldown");
            }

            // Only disable links if there's a cooldown
            if (cooldownPeriod > 0) {
                console.log("Disabling links. cooldownPeriod:", cooldownPeriod);

                // Remove any existing cooldown message
                const existingMsg = document.getElementById('cooldownMessage');
                if (existingMsg) {
                    existingMsg.remove();
                }

                // Disable resend link
                const smsLink = document.getElementById('resendSmsLink');
                console.log("SMS link element:", smsLink);
                if (smsLink) {
                    smsLink.style.color = '#9aa0a6';
                    smsLink.style.pointerEvents = 'none';
                    console.log("SMS link disabled");
                } else {
                    console.log("SMS link element not found!");
                }

                // Show cooldown message
                const cooldownMsg = document.createElement('p');
                cooldownMsg.id = 'cooldownMessage';
                cooldownMsg.style.fontSize = '12px';
                cooldownMsg.style.color = '#5f6368';
                cooldownMsg.style.marginTop = '8px';

                // Format time display
                let timeDisplay;
                if (cooldownPeriod >= 3600) {
                    const hours = Math.floor(remaining / 3600);
                    const minutes = Math.floor((remaining % 3600) / 60);
                    timeDisplay = `${hours} hour${hours > 1 ? 's' : ''}${minutes > 0 ? ' ' + minutes + ' minute' + (minutes > 1 ? 's' : '') : ''}`;
                } else {
                    timeDisplay = `${remaining} second${remaining !== 1 ? 's' : ''}`;
                }

                cooldownMsg.innerHTML = `Please wait <span id="cooldownTimer">${timeDisplay}</span> before resending`;

                const linksDiv = document.querySelector('.verify-links div');
                linksDiv.appendChild(cooldownMsg);

                // Update timer every second
                const timer = setInterval(() => {
                    remaining--;
                    const timerSpan = document.getElementById('cooldownTimer');
                    if (timerSpan) {
                        // Update time display
                        if (cooldownPeriod >= 3600) {
                            const hours = Math.floor(remaining / 3600);
                            const minutes = Math.floor((remaining % 3600) / 60);
                            const seconds = remaining % 60;
                            if (hours > 0) {
                                timerSpan.textContent = `${hours} hour${hours > 1 ? 's' : ''} ${minutes} minute${minutes > 1 ? 's' : ''} ${seconds} second${seconds !== 1 ? 's' : ''}`;
                            } else if (minutes > 0) {
                                timerSpan.textContent = `${minutes} minute${minutes > 1 ? 's' : ''} ${seconds} second${seconds !== 1 ? 's' : ''}`;
                            } else {
                                timerSpan.textContent = `${seconds} second${seconds !== 1 ? 's' : ''}`;
                            }
                        } else {
                            const minutes = Math.floor(remaining / 60);
                            const seconds = remaining % 60;
                            if (minutes > 0) {
                                timerSpan.textContent = `${minutes} minute${minutes > 1 ? 's' : ''} ${seconds} second${seconds !== 1 ? 's' : ''}`;
                            } else {
                                timerSpan.textContent = `${seconds} second${seconds !== 1 ? 's' : ''}`;
                            }
                        }
                    }

                    if (remaining <= 0) {
                        clearInterval(timer);
                        // Re-enable link
                        if (smsLink) {
                            smsLink.style.color = '#FF6B35';
                            smsLink.style.pointerEvents = 'auto';
                        }
                        // Remove cooldown message
                        const msg = document.getElementById('cooldownMessage');
                        if (msg) {
                            msg.remove();
                        }
                    }
                }, 1000);
            }
        }

        // Start cooldown timer if already in cooldown
        <?php if (!$can_resend): ?>
            document.addEventListener('DOMContentLoaded', function() {
                startCooldownTimer(<?php echo $cooldown_remaining; ?>, <?php echo $resend_attempt_count; ?>);
            });
        <?php endif; ?>

        // Remove any existing cooldown message when resend is clicked
        document.getElementById('resendSmsLink')?.addEventListener('click', function() {
            const existingMsg = document.getElementById('cooldownMessage');
            if (existingMsg) {
                existingMsg.remove();
            }
        });
    </script>
</body>
</html>
