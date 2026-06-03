<?php
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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include_once '../config.php';

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email)) {
    // Sanitize email
    $email = $data->email;
    
    // Check if user exists and is not already verified
    $stmt = $pdo->prepare("SELECT id, name, is_verified FROM users WHERE email = ?");
    $stmt->bindParam(1, $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            "success" => false,
            "message" => "No account found with this email."
        ]);
        exit();
    }
    
    if ($user['is_verified']) {
        echo json_encode([
            "success" => false,
            "message" => "This email is already verified. Please login."
        ]);
        exit();
    }
    
    // Generate new verification code
    $verification_code = rand(100000, 999999);
    $code_expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Update user with new verification code
    $update_stmt = $pdo->prepare("UPDATE users SET verification_code = ?, code_expires_at = ? WHERE email = ?");
    $update_stmt->bindParam(1, $verification_code, PDO::PARAM_STR);
    $update_stmt->bindParam(2, $code_expires_at, PDO::PARAM_STR);
    $update_stmt->bindParam(3, $email, PDO::PARAM_STR);
    
    if ($update_stmt->execute()) {
        // Send verification email
        $mail = new PHPMailer(true);
        $email_sent = false;
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'otienobrian029@gmail.com'; // Your Gmail
            $mail->Password   = 'dwuunoftzkodeome';         // App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('otienobrian029@gmail.com', 'Kenya EduHub');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Email Verification - Kenya EduHub';
            $mail->Body    = "
                <p>Hello {$user['name']},</p>
                <p>Your new verification code is: <strong>$verification_code</strong></p>
                <p>Please enter this code to verify your account.</p>
                <p>This code expires in 1 hour.</p>
            ";

            $mail->send();
            $email_sent = true;
        } catch (Exception $e) {
            // Log the error but continue
            error_log("Email verification resend failed for $email: " . $mail->ErrorInfo);
        }
        
        echo json_encode([
            "success" => true,
            "message" => $email_sent 
                ? "Verification code resent successfully. Please check your email." 
                : "Verification code generated. Please contact support for your verification code."
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to generate verification code. Please try again."
        ]);
    }
} else {
    // Missing required fields
    echo json_encode([
        "success" => false,
        "message" => "Email is required"
    ]);
}

$pdo = null;
?>