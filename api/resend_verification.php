<?php
// Suppress errors and warnings to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

header("Content-Type: application/json");

// Remove CORS restrictions for local development
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

// Include PHPMailer
require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';
require_once '../includes/helpers.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include_once '../config.php';

// Start session for cooldown tracking
session_start();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

$email = $data->email ?? null;
$phone = $data->phone ?? null;
$method = $data->method ?? 'email'; // Default to email

if (!$email && !$phone) {
    echo json_encode([
        "success" => false,
        "message" => "Email or phone is required"
    ]);
    exit();
}

// Find user by email or phone
$user = null;

if ($email) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
}

if (!$user && $phone) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
}

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "User not found"
    ]);
    exit();
}

// Check if user is already verified
if ($user['is_verified'] == 1) {
    echo json_encode([
        "success" => false,
        "message" => "Your account is already verified. You can login."
    ]);
    exit();
}

// Generate new verification code
$verification_code = rand(100000, 999999);
$code_expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Update user with new code
$update_stmt = $conn->prepare("UPDATE users SET verification_code = ?, code_expires_at = ? WHERE id = ?");
$update_stmt->bind_param("ssi", $verification_code, $code_expires_at, $user['id']);

if (!$update_stmt->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to generate new verification code"
    ]);
    exit();
}

// Send verification based on method
$email_sent = false;
$sms_sent = false;

if ($method === 'email' || $method === 'both') {
    // Send via email
    $mail = new PHPMailer(true);
    
    try {
        // Get SMTP settings from database
        $smtp_settings = getSMTPSettings($conn);
        
        if (!$smtp_settings) {
            throw new Exception("SMTP settings not configured in database");
        }
        
        $smtp_host = $smtp_settings['smtp_host'];
        $smtp_port = $smtp_settings['smtp_port'];
        $smtp_username = $smtp_settings['smtp_username'];
        $smtp_password = $smtp_settings['smtp_password'];
        $email_from = $smtp_settings['email_from'];
        $encryption = $smtp_settings['encryption'];
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = $encryption;
        $mail->Port = $smtp_port;

        // Recipients
        $mail->setFrom($email_from, 'Kenya EduHub');
        $mail->addAddress($user['email']);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification - Kenya EduHub';
        $mail->Body    = "
            <p>Your new verification code is: <strong>$verification_code</strong></p>
            <p>Please enter this code to verify your account.</p>
            <p>This code expires in 1 hour.</p>
        ";

        $mail->send();
        $email_sent = true;
    } catch (Exception $e) {
        // Log the error but continue
        error_log("Email verification failed for {$user['email']}: " . $mail->ErrorInfo);
    }
}

if (($method === 'sms' || $method === 'both') && $user['phone']) {
    // Send via SMS
    error_log("Resend: Attempting to send SMS to {$user['phone']} for verification code $verification_code");
    $sms_sent = sendSMSVerification($user['phone'], $verification_code);
    error_log("Resend: SMS send result: " . ($sms_sent ? 'SUCCESS' : 'FAILED'));
}

// Build success message based on method
if ($method === 'email') {
    $message = "New verification code sent to your email.";
    $api_success = $email_sent;
} elseif ($method === 'sms') {
    if ($sms_sent) {
        $message = "New verification code sent to your phone via SMS.";
        $api_success = true;
    } else {
        $message = "Failed to send SMS. Please try via email.";
        $api_success = false;
    }
} else {
    $message = "New verification code sent.";
    $api_success = ($email_sent || $sms_sent);
}

// Only set cooldown and increment attempt count if successful
if ($api_success) {
    $_SESSION['last_resend_time'] = time();
    $_SESSION['resend_attempt_count'] = ($_SESSION['resend_attempt_count'] ?? 0) + 1;
}

echo json_encode([
    "success" => $api_success,
    "message" => $message,
    "attempt_count" => $_SESSION['resend_attempt_count'] ?? 0
]);

$conn->close();
?>
