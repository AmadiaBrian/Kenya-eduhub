<?php
session_start();

$error = $_SESSION['register_error'] ?? '';

function showError($error) {
    return !empty($error) ? "<div class='alert alert-danger text-center'>$error</div>" : "";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Registration - Kenya Eduhub</title>
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
        .register-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
        }
        .register-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .register-logo h2 {
            color: #153b50;
            font-weight: 700;
        }
        .form-control {
            height: 45px;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .btn-register {
            background: #153b50;
            border: none;
            padding: 10px 20px;
            font-weight: 600;
            width: 100%;
            height: 45px;
        }
        .btn-register:hover {
            background: #0d2b3a;
        }
        .back-to-login {
            text-align: center;
            margin-top: 1.5rem;
        }
        .back-to-login a {
            color: #153b50;
            text-decoration: none;
            font-weight: 500;
        }
        .admin-key {
            position: relative;
        }
        .admin-key .form-text {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="register-card animate__animated animate__fadeIn">
                    <div class="register-logo">
                        <h2>Admin Registration</h2>
                        <p class="text-muted">Create a new admin account</p>
                    </div>
                    
                    <?= showError($error); ?>
                    
                    <form action="admin_register_process.php" method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <input type="text" name="name" class="form-control" placeholder="Full Name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm Password" required>
                                    <div class="invalid-feedback">Passwords do not match!</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" name="register" class="btn btn-primary btn-register">Register as Admin</button>
                        </div>
                    </form>
                    
                    <div class="back-to-login">
                        Already have an account? <a href="admin_login.php">Login here</a>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="index.php" class="text-decoration-none">← Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password match validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password');
            
            if (password !== confirmPassword.value) {
                e.preventDefault();
                confirmPassword.classList.add('is-invalid');
            } else {
                confirmPassword.classList.remove('is-invalid');
            }
        });
    </script>
</body>
</html>

<?php
// Clear error after displaying
unset($_SESSION['register_error']);
?>
