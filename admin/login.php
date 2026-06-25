<?php
session_start();

// Include security first
require_once '../includes/security_lite.php';

require_once '../config.php';

// Security: Rate limiting for admin login
$admin_login_identifier = $_SERVER['REMOTE_ADDR'] . '_admin_login';
if (!checkRateLimit($admin_login_identifier, 5, 900)) { // 5 attempts per 15 minutes (stricter for admin)
    $_SESSION['error'] = "Too many admin login attempts. Please try again in 15 minutes.";
    header("Location: login.php");
    exit();
}

// Security: Check if user is already logged in as admin
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && $user['role'] === 'admin') {
        header("Location: index.php");
        exit();
    } else {
        // If logged in but not admin, logout first
        session_destroy();
    }
}

// Security: Generate CSRF token for admin login
if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Security: CSRF validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
        // Regenerate token after failed attempt
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    } else {
        // Security: Sanitize inputs
        $email = sanitizeStrict(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        
        // Security: Input validation
        if (empty($email) || empty($password)) {
            $error = "Please fill in all fields";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format";
        } elseif (strlen($password) > 256) {
            $error = "Password too long";
        } else {
            // Check if user exists and is admin
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    // Security: Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    // Set secure session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['admin_login_time'] = time();
                    $_SESSION['admin_last_activity'] = time();
                    
                    // Security: Log successful admin login
                    logSecurityEvent('ADMIN_LOGIN_SUCCESS', [
                        'user_id' => $user['id'],
                        'email' => $user['email'],
                        'ip' => $_SERVER['REMOTE_ADDR'],
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                    ]);
                    
                    // Security: Clear CSRF token after successful login
                    unset($_SESSION['admin_csrf_token']);
                    
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Invalid password";
                    // Security: Log failed admin login attempt
                    logSecurityEvent('ADMIN_LOGIN_FAILED', [
                        'email' => $email,
                        'ip' => $_SERVER['REMOTE_ADDR'],
                        'reason' => 'Invalid password',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                    ]);
                }
            } else {
                $error = "Admin user not found or access denied";
                // Security: Log failed admin login attempt
                logSecurityEvent('ADMIN_LOGIN_FAILED', [
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'reason' => 'User not found or not admin',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ]);
            }
            // Security: Regenerate CSRF token after each attempt
            $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Kenya EduHub</title>
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdnjs.cloudflare.com; img-src 'self' data:;">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Base - Matching auth/login.php */
        body {
            background: url('../assets/images/back.jpg') center/cover fixed;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
            color: #222831;
            will-change: auto;
            contain: layout style paint;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('../assets/images/back.jpg') center/cover;
            background-attachment: fixed;
            animation: imageZoom 20s ease-in-out infinite alternate;
            z-index: -2;
        }

        body::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, 
                rgba(9, 29, 31, 0.4) 0%, 
                rgba(11, 79, 63, 0.3) 50%, 
                rgba(30, 60, 114, 0.2) 100%);
            z-index: -1;
            animation: overlayShift 15s ease-in-out infinite;
        }

        @keyframes imageZoom {
            0% {
                transform: scale(1) translateX(0) translateY(0);
                filter: brightness(1) contrast(1);
            }
            25% {
                transform: scale(1.1) translateX(-2%) translateY(-1%);
                filter: brightness(1.1) contrast(1.1);
            }
            50% {
                transform: scale(1.15) translateX(1%) translateY(-2%);
                filter: brightness(1.05) contrast(1.05);
            }
            75% {
                transform: scale(1.05) translateX(-1%) translateY(1%);
                filter: brightness(1.15) contrast(1.15);
            }
            100% {
                transform: scale(1.2) translateX(0) translateY(0);
                filter: brightness(1.2) contrast(1.2);
            }
        }

        @keyframes overlayShift {
            0%, 100% {
                opacity: 0.7;
                background: linear-gradient(135deg, 
                    rgba(9, 29, 31, 0.4) 0%, 
                    rgba(11, 79, 63, 0.3) 50%, 
                    rgba(30, 60, 114, 0.2) 100%);
            }
            33% {
                opacity: 0.5;
                background: linear-gradient(225deg, 
                    rgba(30, 60, 114, 0.3) 0%, 
                    rgba(9, 29, 31, 0.4) 50%, 
                    rgba(11, 79, 63, 0.2) 100%);
            }
            66% {
                opacity: 0.6;
                background: linear-gradient(315deg, 
                    rgba(11, 79, 63, 0.4) 0%, 
                    rgba(30, 60, 114, 0.3) 50%, 
                    rgba(9, 29, 31, 0.2) 100%);
            }
        }

        /* Card - Glassy Effect */
        .login-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px) saturate(1.8);
            max-width: 420px;
            width: 100%;
            padding: 3rem 2.5rem 2.5rem;
            border-radius: 24px;
            box-shadow: 
                0 20px 45px rgba(0,0,0,0.15),
                0 0 0 1px rgba(255, 255, 255, 0.2),
                inset 0 0 30px rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideUp 0.7s ease forwards;
            user-select: none;
            will-change: transform, opacity;
            transform: translateZ(0);
            contain: layout style paint;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.1) 0%, 
                rgba(255, 255, 255, 0.05) 100%);
            border-radius: 24px;
            z-index: -1;
            transition: all 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 25px 55px rgba(0,0,0,0.2),
                0 0 0 1px rgba(255, 255, 255, 0.3),
                inset 0 0 40px rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .login-card:hover::before {
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.15) 0%, 
                rgba(255, 255, 255, 0.08) 100%);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card h3 {
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 1.75rem;
            text-align: center;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .form-control {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #222831;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(30, 60, 114, 0.5);
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
            color: #222831;
        }

        .form-control::placeholder {
            color: rgba(34, 40, 49, 0.6);
        }

        .btn-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 8px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(30, 60, 114, 0.3);
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: #dc3545;
            backdrop-filter: blur(10px);
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid rgba(40, 167, 69, 0.2);
            color: #28a745;
            backdrop-filter: blur(10px);
        }

        .admin-security-notice {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.2);
            color: #ffc107;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.875rem;
            backdrop-filter: blur(10px);
            text-align: center;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(34, 40, 49, 0.6);
            cursor: pointer;
            z-index: 10;
        }

        .password-toggle:hover {
            color: rgba(34, 40, 49, 0.8);
        }

        .back-link {
            text-align: center;
            margin-top: 24px;
        }

        .back-link a {
            color: rgba(30, 60, 114, 0.8);
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .back-link a:hover {
            color: #1e3c72;
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 2rem 1.5rem 2rem;
            }
            .btn-primary {
                padding: 10px 20px;
            }
        }

        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            .login-card {
                animation: none;
            }
            body::before,
            body::after {
                animation: none;
            }
        }

        /* Form inputs - Matching auth/login.php */
        input.form-control {
            height: 48px;
            font-size: 1rem;
            border-radius: 0.625rem;
            border: 1.8px solid #ced4da;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            will-change: border-color, box-shadow;
        }

        input.form-control:focus {
            border-color: #1e3c72;
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
        }

        /* Button styling */
        button.btn-primary {
            height: 48px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 0.625rem;
            transition: all 0.3s ease;
            will-change: transform, box-shadow;
        }

        button.btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(30, 60, 114, 0.3);
        }

        /* Alert styling */
        .alert {
            border-radius: 0.625rem;
            margin-bottom: 1.5rem;
        }

        /* Additional admin-specific styles */
        .admin-badge {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: inline-block;
        }

        .lock-icon {
            color: #ffc107;
            margin-right: 0.5rem;
        }

        .admin-footer-links {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-footer-links a {
            color: rgba(30, 60, 114, 0.8);
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .admin-footer-links a:hover {
            color: #1e3c72;
            text-decoration: underline;
        }

        /* Password toggle button - Matching auth/login.php */
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: color 0.2s ease, background-color 0.2s ease;
            z-index: 10;
        }

        .password-toggle:hover {
            color: #4ea1ff;
            background-color: rgba(78, 161, 255, 0.1);
        }

        .password-toggle:focus {
            outline: 2px solid #4ea1ff;
            outline-offset: 2px;
        }

        /* Button - Matching auth/login.php */
        button.btn-primary {
            width: 100%;
            height: 48px;
            font-weight: 700;
            font-size: 1.125rem;
            border-radius: 0.75rem;
            background: linear-gradient(90deg, #1e3c72, #4ea1ff);
            border: none;
            transition: background 0.3s ease, transform 0.2s ease;
            box-shadow: 0 8px 20px rgb(78 161 255 / 0.5);
            user-select: none;
            will-change: transform, background;
            position: relative;
            overflow: hidden;
        }

        button.btn-primary:hover,
        button.btn-primary:focus-visible {
            background: linear-gradient(90deg, #4ea1ff, #1e3c72);
            transform: scale(1.05) translateZ(0);
            box-shadow: 0 12px 30px rgb(78 161 255 / 0.7);
            outline-offset: 3px;
            outline: 3px solid #1e3c72;
        }

        /* Link styling - Matching auth/login.php */
        p.text-center small {
            color: #495057;
            user-select: none;
        }

        p.text-center small a {
            color: #4ea1ff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.25s ease;
        }

        p.text-center small a:hover,
        p.text-center small a:focus-visible {
            color: #1e3c72;
            text-decoration: underline;
            outline-offset: 2px;
        }

        /* Admin-specific styling */
        .admin-badge {
            background: linear-gradient(90deg, #1e3c72, #4ea1ff);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .alert {
            border-radius: 0.625rem;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .alert-success {
            background: rgba(25, 135, 84, 0.1);
            color: #155724;
            border-left: 4px solid #198754;
        }

        .form-floating label {
            color: #495057;
        }

        .form-floating > .form-control:focus ~ label {
            color: #4ea1ff;
        }

        /* Admin security notice */
        .admin-security-notice {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 0.625rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            color: #856404;
        }

        .admin-security-notice i {
            margin-right: 0.5rem;
            color: #ffc107;
        }

        /* Footer links */
        .admin-footer-links {
            text-align: center;
            margin-top: 2rem;
        }

        .admin-footer-links a {
            color: #4ea1ff;
            text-decoration: none;
            font-size: 0.875rem;
            margin: 0 1rem;
            transition: color 0.3s ease;
        }

        .admin-footer-links a:hover {
            color: #1e3c72;
            text-decoration: underline;
        }

        /* Loading state */
        .btn-primary.loading {
            color: transparent;
            pointer-events: none;
        }

        .btn-primary.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Mobile Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 2rem 1.5rem;
                margin: 0.5rem;
            }
        }

        /* Auth page match */
        :root {
            --primary-orange: #FF6B35;
            --primary-gold: #FFD700;
        }

        html,
        body {
            background: #000000 !important;
            background-image: none !important;
        }

        body {
            margin: 0;
            padding: 1rem;
            color: #ffffff;
            overflow: auto;
        }

        body::before,
        body::after,
        .login-card::before {
            display: none !important;
        }

        .login-card,
        .login-card:hover {
            background: #000000;
            max-width: 420px;
            padding: 3rem 2.5rem 2.5rem;
            border: none;
            border-radius: 0;
            box-shadow: none;
            animation: none;
            transform: none;
            transition: none;
        }

        .logo-img {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .auth-brand-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            color: #ffffff;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .auth-brand-logo .brand-mark {
            width: 50px;
            height: 50px;
            background: var(--primary-gold);
            border: 3px solid var(--primary-orange);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 2px;
        }

        .auth-brand-logo .brand-text {
            line-height: 1;
        }

        .login-card h3 {
            color: #ffffff;
            text-shadow: none;
        }

        .login-card h3::first-letter {
            color: var(--primary-orange);
        }

        .admin-badge,
        .admin-security-notice,
        .form-text,
        .form-floating label {
            display: none;
        }

        .text-muted,
        .form-check-label {
            color: #666666 !important;
        }

        input.form-control,
        .form-floating > .form-control {
            height: 48px;
            font-size: 1rem;
            border-radius: 0;
            border: 2px solid #ffffff;
            background: #000000;
            color: #ffffff !important;
            transition: none;
            box-shadow: none;
            padding: 0.375rem 0.75rem;
        }

        input.form-control::placeholder {
            color: #888888 !important;
            opacity: 1;
        }

        input.form-control:-webkit-autofill,
        input.form-control:-webkit-autofill:hover,
        input.form-control:-webkit-autofill:focus {
            -webkit-text-fill-color: #ffffff !important;
            -webkit-box-shadow: 0 0 0 1000px #000000 inset;
            transition: background-color 5000s ease-in-out 0s;
        }

        input.form-control:focus,
        .form-floating > .form-control:focus {
            border: 2px solid #333333;
            background: #000000;
            color: #ffffff !important;
            outline: none;
            box-shadow: none;
            transform: none;
        }

        .form-check-input {
            background-color: #000000;
            border: 1px solid #ffffff;
            border-radius: 0;
        }

        .form-check-input:checked {
            background-color: var(--primary-orange);
            border-color: var(--primary-orange);
        }

        button.btn-primary,
        button.btn-primary:hover,
        button.btn-primary:focus-visible {
            width: 100%;
            height: 48px;
            font-weight: 700;
            font-size: 1.125rem;
            border-radius: 0;
            background: #000000;
            border: 1px solid #333333;
            color: #ffffff;
            box-shadow: none;
            transform: none;
            transition: none;
            outline: none;
        }

        button.btn-primary:hover,
        button.btn-primary:focus-visible {
            background: #111111;
            border-color: #444444;
        }

        .password-toggle,
        .password-toggle:hover,
        .password-toggle:focus {
            color: #666666;
            background: transparent;
            border: none;
            border-radius: 0;
            outline: none;
        }

        .password-toggle:hover {
            color: #888888;
        }

        .alert-danger,
        .alert-success {
            background: #000000;
            border-radius: 0;
            text-align: center;
            box-shadow: none;
        }

        .alert-danger {
            color: #ff0000;
            border: 1px solid #ff0000;
        }

        .alert-success {
            color: #666666;
            border: 1px solid #333333;
        }

        .admin-footer-links {
            display: block;
            margin-top: 1.5rem;
            padding-top: 0;
            border-top: none;
            text-align: center;
        }

        .admin-footer-links a {
            display: inline-flex;
            margin: 0.35rem 0.75rem;
            color: #888888;
            font-weight: 600;
        }

        .admin-footer-links a:hover,
        .admin-footer-links a:focus-visible {
            color: #aaaaaa;
            text-decoration: underline;
            outline: none;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 2rem 1.5rem;
                margin: 0;
            }

            button.btn-primary {
                height: 44px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <main class="login-card" role="main" aria-label="Admin Login Form">
        <div class="logo-img">
            <div class="auth-brand-logo" aria-label="Kenya EduHub Logo">
                <div class="brand-mark">
                    <span style="font-weight: bold; font-size: 24px;">
                        <span style="color: var(--primary-orange); font-size: 28px;">K</span><span style="color: #008000; font-size: 24px;">E</span>
                    </span>
                </div>
                <span class="brand-text"><span style="color: var(--primary-orange);">Kenya</span> <span style="color: #008000;">EduHub</span></span>
            </div>
        </div>

        <!-- Admin Badge -->
        <div class="admin-badge">
            <i class="fas fa-shield-alt"></i>
            Admin Access
        </div>

        <h3>Admin Login</h3>
        <p class="text-center text-muted mb-4">Kenya EduHub Management System</p>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Security Notice -->
        <div class="admin-security-notice">
            <i class="fas fa-lock"></i>
            <strong>Security Notice:</strong> This area is restricted to authorized administrators only. All login attempts are logged.
        </div>

        <!-- Login Form -->
        <form method="POST" id="adminLoginForm">
            <!-- Security: CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">
            
            <div class="mb-4">
                <label for="email" class="visually-hidden">Admin email address</label>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="Admin Email" required
                       autocomplete="username"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="mb-4 position-relative">
                <label for="password" class="visually-hidden">Password</label>
                <input type="password" class="form-control pe-5" id="password" name="password" 
                       placeholder="Password" required
                       autocomplete="current-password">
                <span class="password-toggle" onclick="togglePassword()" role="button" tabindex="0" aria-label="Show or hide password">
                    <i class="fa-solid fa-eye" id="passwordToggleIcon"></i>
                </span>
            </div>

            <button type="submit" class="btn btn-primary" id="loginBtn">
                <i class="fas fa-sign-in-alt me-2"></i> Sign In to Admin Panel
            </button>
        </form>

        <!-- Footer Links -->
        <div class="admin-footer-links">
            <a href="../dashboard">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
            <a href="../">
                <i class="fas fa-home me-1"></i> Homepage
            </a>
        </div>
    </main>

    <script>
        // Form submission handling
        document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const originalText = btn.innerHTML;
            
            // Security: Disable button to prevent double submission
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Authenticating...';
            
            // Security: Clear password field after 3 seconds (in case of error)
            setTimeout(() => {
                if (btn.disabled) {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    document.getElementById('password').value = '';
                }
            }, 3000);
        });
        
        // Security: Mask password visibility toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Security: Check for URL parameters indicating timeout
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('timeout') === '1') {
            const errorDiv = document.querySelector('.alert-danger');
            if (errorDiv) {
                errorDiv.textContent = 'Session expired due to inactivity. Please log in again.';
                errorDiv.style.display = 'block';
            }
        }
        
        // Security: Prevent right-click on password field
        document.getElementById('password').addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });
        
        // Security: Clear form data on page unload
        window.addEventListener('beforeunload', function() {
            document.getElementById('password').value = '';
        });
        
        // Dynamic gradient animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes gradientShift {
                0% { background-position: 0% 50%; }
                25% { background-position: 100% 50%; }
                50% { background-position: 100% 100%; }
                75% { background-position: 0% 100%; }
                100% { background-position: 0% 50%; }
            }
        `;
        document.head.appendChild(style);
        
        // Add interactive effects matching auth/login.php
        document.addEventListener('DOMContentLoaded', function() {
            // Add ripple effect to button
            const button = document.getElementById('loginBtn');
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.style.position = 'absolute';
                ripple.style.borderRadius = '50%';
                ripple.style.background = 'rgba(255, 255, 255, 0.5)';
                ripple.style.transform = 'scale(0)';
                ripple.style.animation = 'ripple 0.6s ease-out';
                ripple.style.pointerEvents = 'none';
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Add ripple animation
        const rippleStyle = document.createElement('style');
        rippleStyle.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(rippleStyle);
    </script>
</body>
</html>
