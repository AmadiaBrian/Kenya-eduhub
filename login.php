<?php
session_start();

$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? '',
];
$activeForm = $_SESSION['active_form'] ?? 'login';

function showError($error) {
    return !empty($error) ? "<div class='alert alert-danger text-center'>$error</div>" : "";
}

function isActiveTab($formName, $activeForm): string {
    return $formName === $activeForm ? 'active' : '';
}

function isActivePane($formName, $activeForm): string {
    return $formName === $activeForm ? 'show active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kenya Eduhub - Free Education Resources</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="login.css" />
</head>
<body>
    <div class="main-wrapper d-flex justify-content-center align-items-center vh-100 bg-gradient">
        <div class="card shadow-lg rounded-4 p-4 animate__animated animate__fadeIn" style="width: 100%; max-width: 480px;">
            <div class="text-center mb-4">
                <h3 class="fw-bold text-primary">Kenya Eduhub</h3>
                <p class="text-muted small">Free Education Resources for All</p>
            </div>

            <!-- Nav Tabs -->
            <ul class="nav nav-tabs nav-justified mb-4" id="authTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= isActiveTab('login', $activeForm) ?>" id="login-tab"
                        data-bs-toggle="tab" data-bs-target="#login-form" type="button" role="tab">
                        Login
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= isActiveTab('register', $activeForm) ?>" id="register-tab"
                        data-bs-toggle="tab" data-bs-target="#register-form" type="button" role="tab">
                        Register
                    </button>
                </li>
            </ul>

            <!-- Form Tabs -->
            <div class="tab-content">
                <!-- Login Form -->
                <div class="tab-pane fade <?= isActivePane('login', $activeForm); ?>" id="login-form" role="tabpanel">
                    <form action="login_register.php" method="post" class="animate__animated animate__fadeInRight">
                        <?= showError($errors['login']); ?>
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control form-control-lg" placeholder="Email" required />
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" class="form-control form-control-lg" placeholder="Password" required />
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" name="login" class="btn btn-primary btn-lg">Login</button>
                        </div>
                        <div class="text-center mt-3">
                            <a href="request_verification.php" class="text-decoration-none">Didn't receive a code? Request Verification</a>
                        </div>
                        <div class="text-center">
                            <a href="forgot_password.php" class="small text-decoration-none">Forgot Password?</a>
                        </div>
                    </form>
                </div>

                <!-- Register Form -->
                <div class="tab-pane fade <?= isActivePane('register', $activeForm); ?>" id="register-form" role="tabpanel">
                    <form action="login_register.php" method="post" class="animate__animated animate__fadeInLeft">
                        <?= showError($errors['register']); ?>
                        <div class="mb-3">
                            <input type="text" name="name" class="form-control form-control-lg" placeholder="Full Name" required />
                        </div>
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control form-control-lg" placeholder="Email" required />
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" class="form-control form-control-lg" placeholder="Password" required />
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" name="register" class="btn btn-success btn-lg">Register as User</button>
                        </div>
                        <div class="text-center mt-3">
                            <p class="mb-0">Are you an admin? <a href="admin_login.php" class="fw-bold">Login here</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Clear errors AFTER rendering so verification session stays intact
unset($_SESSION['login_error']);
unset($_SESSION['register_error']);
unset($_SESSION['active_form']);
?>
