<?php
session_start();

$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? '',
];
$activeForm = $_SESSION['active_form'] ?? 'login';

// Clear session errors after displaying
session_unset();

function showError($error) {
    return !empty($error) ? "<p class='error-message'>$error</p>" : "";
}

function isActiveForm($formName, $activeForm): string {
    return $formName === $activeForm ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kenya Eduhub Free Education Resources</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <!-- Login Form -->
        <div class="form-box <?= isActiveForm('login', $activeForm); ?>" id="login-form">
            <form action="login_register.php" method="post">
                <h2>Login</h2>
                <?= showError($errors['login']); ?>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
                <p>Don't have an account? <a href="#" onclick="showForm('register-form')">Register</a></p>
            </form>
        </div>

        <!-- Register Form -->
        <div class="form-box <?= isActiveForm('register', $activeForm); ?>" id="register-form">
            <form action="login_register.php" method="post">
                <h2>Register</h2>
                <?= showError($errors['register']); ?>
                <input type="text" name="name" placeholder="Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <select name="role" required>
                    <option value="">------Select Role------</option>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit" name="register">Register</button>
                <p>Already have an account? <a href="#" onclick="showForm('login-form')">Login</a></p>
            </form>
        </div>
    </div>

    <script>
        // Basic JavaScript to toggle forms
        function showForm(formId) {
            document.getElementById('login-form').classList.remove('active');
            document.getElementById('register-form').classList.remove('active');
            document.getElementById(formId).classList.add('active');
        }
    </script>

    <script src="other.js"></script>
</body>
</html>