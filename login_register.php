<?php
ob_start(); // Start output buffering to allow header redirects
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once 'config.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ------------------------------
// Function to send verification
// ------------------------------
function sendVerificationCode($email, $name, $code) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'otienobrian029@gmail.com';     // Your Gmail
        $mail->Password   = 'dwuunoftzkodeome';             // Your Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('otienobrian029@gmail.com', 'Kenya Eduhub');
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Your Kenya Eduhub Verification Code';
        $mail->Body    = "
            <p>Hello <strong>$name</strong>,</p>
            <p>Your verification code is:</p>
            <h2>$code</h2>
            <p>This code will expire in 10 minutes.</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        file_put_contents("mail_error.log", $e->getMessage(), FILE_APPEND);
        return false;
    }
}

// ------------------------------
// REGISTRATION SECTION - Regular users only
// ------------------------------
if (isset($_POST['register'])) {
    $name     = $conn->real_escape_string(trim($_POST['name']));
    $email    = $conn->real_escape_string(trim($_POST['email']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = 'user'; // Force role to be 'user' for regular registration
    $code     = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires  = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $is_verified = 0;

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = 'Invalid email format';
        $_SESSION['active_form'] = 'register';
        header("Location: login.php");
        exit();
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['register_error'] = 'Email is already registered!';
        $_SESSION['active_form'] = 'register';
        header("Location: login.php");
        exit();
    }
    $stmt->close();

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, verification_code, code_expires_at, is_verified)
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $name, $email, $password, $role, $code, $expires, $is_verified);
    if ($stmt->execute()) {
        if (sendVerificationCode($email, $name, $code)) {
            $_SESSION['pending_user_email'] = $email;
            $_SESSION['success_message'] = "Verification code sent to $email.";
            header("Location: verify_code.php");
            exit();
        } else {
            $_SESSION['register_error'] = 'Could not send verification code. Try again.';
        }
    } else {
        $_SESSION['register_error'] = 'Registration failed. Try again.';
    }

    $_SESSION['active_form'] = 'register';
    header("Location: login.php");
    exit();
}

// ------------------------------
// LOGIN SECTION
// ------------------------------
if (isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT name, email, password, role, is_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (!password_verify($password, $user['password'])) {
            $_SESSION['login_error'] = 'Incorrect password.';
            $_SESSION['active_form'] = 'login';
            header("Location: login.php");
            exit();
        }

        if (!$user['is_verified']) {
            $_SESSION['pending_user_email'] = $email;
            $_SESSION['success_message'] = 'Please verify your email before logging in.';
            header("Location: verify_code.php");
            exit();
        }

        $_SESSION['name']  = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role']  = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: admin_page.php");
        } else {
            header("Location: user_page.php");
        }
        exit();
    }

    $_SESSION['login_error'] = 'Account not found.';
    $_SESSION['active_form'] = 'login';
    header("Location: login.php");
    exit();
}

ob_end_flush(); // Send buffered output (safe after all headers are sent)
