<?php
// Finance Manager Login Page
// Session is started by index.php router
require_once __DIR__ . '/../config.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['finance_manager_id'])) {
    header('Location: dashboard');
    exit;
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT fml.id, fml.finance_manager_id, fml.password, fm.first_name, fm.last_name, fm.school_id, s.school_name
                                   FROM finance_manager_logins fml
                                   JOIN finance_managers fm ON fml.finance_manager_id = fm.id
                                   JOIN schools s ON fm.school_id = s.id
                                   WHERE fml.email = ? AND fml.is_active = 1 AND fm.status = 'active'");
            $stmt->execute([$email]);
            $finance_manager_login = $stmt->fetch();

            if ($finance_manager_login && password_verify($password, $finance_manager_login['password'])) {
                // Generate session token
                $session_token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+8 hours'));

                // Store session in database
                $stmt = $pdo->prepare("INSERT INTO finance_manager_sessions (finance_manager_id, session_token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$finance_manager_login['finance_manager_id'], $session_token, $expires_at]);

                // Set session variables
                $_SESSION['finance_manager_id'] = $finance_manager_login['finance_manager_id'];
                $_SESSION['finance_manager_name'] = $finance_manager_login['first_name'] . ' ' . $finance_manager_login['last_name'];
                $_SESSION['school_id'] = $finance_manager_login['school_id'];
                $_SESSION['school_name'] = $finance_manager_login['school_name'];
                $_SESSION['finance_manager_session_token'] = $session_token;

                header('Location: dashboard');
                exit;
            } else {
                $error = 'Invalid email or password';
            }
        } catch (PDOException $e) {
            error_log("Finance manager login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
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
    <title>Finance Manager Login - Kenya EduHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 48px 40px 36px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: #202124;
            margin-bottom: 8px;
        }
        
        .logo-circle {
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
        
        .logo-circle span {
            font-weight: bold;
            font-size: 24px;
        }
        
        .logo p {
            font-size: 16px;
            color: #5f6368;
            margin-bottom: 40px;
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
        
        .btn-primary:active {
            background: #cc4a20;
            border-color: #cc4a20;
        }
        
        .back-link {
            margin-top: 48px;
            text-align: center;
        }
        
        .back-link a {
            color: #FF6B35;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.2s;
        }
        
        .back-link a:hover {
            color: #DAA520;
        }
        
        .alert {
            width: 100%;
            padding: 12px 16px;
            border-radius: 25px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .alert-danger {
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid #f9dedc;
        }
        
        .alert-success {
            background: #e6f4ea;
            color: #137333;
            border: 1px solid #ceead6;
        }
        
        .alert .btn-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 18px;
            padding: 0;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        
        .alert .btn-close:hover {
            opacity: 1;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 24px 20px;
                max-width: 100%;
            }
            
            .logo {
                font-size: 1.25rem;
                gap: 0.5rem;
            }
            
            .logo div:first-child {
                width: 40px;
                height: 40px;
            }
            
            .logo div:first-child span {
                font-size: 20px;
            }
            
            .logo div:first-child span span:first-child {
                font-size: 24px;
            }
            
            h1 {
                font-size: 20px !important;
            }
            
            p {
                font-size: 14px !important;
            }
            
            .form-control {
                padding: 12px 14px;
                font-size: 15px;
            }
            
            .btn-primary {
                padding: 11px 20px;
                font-size: 14px;
            }
            
            .back-link {
                margin-top: 32px;
            }
        }
        
        @media (max-width: 360px) {
            .login-container {
                padding: 20px 16px;
            }
            
            .logo {
                font-size: 1.1rem;
                gap: 0.4rem;
            }
            
            .logo div:first-child {
                width: 36px;
                height: 36px;
            }
            
            .logo div:first-child span {
                font-size: 18px;
            }
            
            .logo div:first-child span span:first-child {
                font-size: 22px;
            }
            
            h1 {
                font-size: 18px !important;
            }
            
            .form-control {
                padding: 11px 12px;
                font-size: 14px;
            }
            
            .btn-primary {
                padding: 10px 16px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div style="width: 50px; height: 50px; background: #FFD700; border: 3px solid #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 2px;">
                <span style="font-weight: bold; font-size: 24px;">
                    <span style="color: #FF6B35; font-size: 28px;">K</span><span style="color: #008000; font-size: 24px;">E</span>
                </span>
            </div>
            <span style="color: #FF6B35;">Kenya</span> <span style="color: #008000;">EduHub</span>
        </div>
        
        <h1 style="text-align: center; font-size: 24px; font-weight: 400; color: #202124; margin-bottom: 8px;">Finance Manager Portal</h1>
        <p style="text-align: center; font-size: 16px; color: #5f6368; margin-bottom: 40px;">Kenya EduHub</p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
        <?php endif; ?>
        
        <form class="login-form" method="POST" id="loginForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required placeholder="Enter your email">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password">
            </div>
            <button type="submit" class="btn-primary">
                Login
            </button>
        </form>
        
        <div class="back-link">
            <a href="../index.php">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
