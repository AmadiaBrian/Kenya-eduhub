<?php
session_start();

$error = $_SESSION['login_error'] ?? '';

function showError($error) {
    return !empty($error) ? "<div class='alert alert-danger text-center'>$error</div>" : "";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Login - Kenya Eduhub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        body {
            background: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #153b50 0%, #3f87a6 100%);
        }
        .login-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo h2 {
            color: #153b50;
            font-weight: 700;
        }
        .form-control {
            height: 45px;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .btn-login {
            background: #153b50;
            border: none;
            padding: 10px 20px;
            font-weight: 600;
            width: 100%;
            height: 45px;
        }
        .btn-login:hover {
            background: #0d2b3a;
        }
        .back-to-home {
            text-align: center;
            margin-top: 1rem;
        }
        .back-to-home a {
            color: #fff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="login-card animate__animated animate__fadeIn">
                    <div class="login-logo">
                        <h2>Admin Login</h2>
                        <p class="text-muted">Access the admin dashboard</p>
                    </div>
                    
                    <?= showError($error); ?>
                    
                    <form action="admin_auth.php" method="post">
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" class="form-control" placeholder="Password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="login" class="btn btn-primary btn-login">Login</button>
                        </div>
                    </form>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <a href="forgot_password.php" class="text-decoration-none">Forgot Password?</a>
                        </div>
                        <div>
                            <span class="text-muted">New admin?</span>
                            <a href="admin_register.php" class="ms-2 fw-bold text-decoration-none">Register Here</a>
                        </div>
                    </div>
                    
                    <div class="back-to-home mt-4 text-center">
                        <a href="index.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i> Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Clear error after displaying
unset($_SESSION['login_error']);
?>
