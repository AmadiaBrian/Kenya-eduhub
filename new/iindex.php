<?php
session_start();
require 'auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: chat.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        if (loginUser($_POST['email'], $_POST['password'])) {
            header('Location: chat.php');
            exit;
        } else {
            $error = "Invalid email or password";
        }
    } elseif (isset($_POST['register'])) {
        if (registerUser($_POST['username'], $_POST['email'], $_POST['password'])) {
            loginUser($_POST['email'], $_POST['password']);
            header('Location: chat.php');
            exit;
        } else {
            $error = "Email already registered!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat System - Login</title>
    <link rel="stylesheet" href="sstyle.css"> <!-- Your external CSS file -->
    <style>
        /* EXTRA CSS for switching forms */
        .form-container {
            width: 300px;
            margin: auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-container h1, .form-container h2 {
            text-align: center;
        }
        form {
            display: none;
        }
        form.active {
            display: block;
        }
        .switch-link {
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
        }
        .switch-link a {
            color: blue;
            cursor: pointer;
            text-decoration: underline;
        }
        .error {
            color: red;
            text-align: center;
        }


        
    </style>
</head>
<body>

<div class="login-container form-container">
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

    <!-- Login Form -->
    <form id="login-form" method="post" class="active">
        <h1>Login</h1>
        <input type="email" name="email" placeholder="Email" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <button type="submit" name="login">Login</button>

        <div class="switch-link">
            Don't have an account? <a onclick="showRegister()">Register</a>
        </div>
    </form>

    <!-- Register Form -->
    <form id="register-form" method="post">
        <h2>Register</h2>
        <input type="text" name="username" placeholder="Username" required><br><br>
        <input type="email" name="email" placeholder="Email" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <button type="submit" name="register">Register</button>

        <div class="switch-link">
            Already have an account? <a onclick="showLogin()">Login</a>
        </div>
    </form>
</div>

<script>
    // JavaScript to switch forms
    function showRegister() {
        document.getElementById('login-form').classList.remove('active');
        document.getElementById('register-form').classList.add('active');
    }

    function showLogin() {
        document.getElementById('register-form').classList.remove('active');
        document.getElementById('login-form').classList.add('active');
    }
</script>

</body>
</html>
