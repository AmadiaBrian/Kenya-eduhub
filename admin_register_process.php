<?php
session_start();
require_once 'config.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['register'])) {
    // Sanitize inputs
    $name = $conn->real_escape_string(trim($_POST['name']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    $errors = [];
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Email is already registered.";
    }
    
    // Check admin limit (max 2 admins)
    $result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
    $row = $result->fetch_assoc();
    if ($row['total'] >= 2) {
        $errors[] = 'Maximum number of admin accounts reached.';
    }
    
    // If there are validation errors, redirect back with errors
    if (!empty($errors)) {
        $_SESSION['register_error'] = implode("<br>", $errors);
        header("Location: admin_register.php");
        exit();
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate verification code
    $verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $code_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Store verification attempt in session
    $_SESSION['admin_verification'] = array(
        'name' => $name,
        'email' => $email,
        'password' => $hashed_password,
        'code' => $verification_code,
        'expires' => $code_expires
    );
    
    // Send verification email
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'otienobrian029@gmail.com'; // Your Gmail
        $mail->Password   = 'dwuunoftzkodeome';         // Your Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('otienobrian029@gmail.com', 'Kenya Eduhub');
        $mail->addAddress($email, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Admin Account Verification - Kenya Eduhub';
        $mail->Body = "
            <h2>Admin Account Verification</h2>
            <p>Hello $name,</p>
            <p>Thank you for registering as an admin for Kenya Eduhub. Please use the following verification code to activate your account:</p>
            <h3 style='color: #153b50;'>$verification_code</h3>
            <p>This code will expire in 10 minutes.</p>
            <p>If you didn't request this, please ignore this email.</p>
        ";

        $mail->send();
        
        // Redirect to verification page
        header("Location: admin_verify.php");
        exit();
        
    } catch (Exception $e) {
        // Log error and redirect with error message
        error_log("Mailer Error: " . $mail->ErrorInfo);
        $_SESSION['register_error'] = 'Failed to send verification email. Please try again.';
        header("Location: admin_register.php");
        exit();
    }
} else {
    $_SESSION['register_error'] = 'Registration failed. Please try again.';
    header("Location: admin_register.php");
    exit();
}

// If not a POST request, redirect to registration page
header("Location: admin_register.php");
exit();
?>
