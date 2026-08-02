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
require_once '../includes/helpers.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include_once '../config.php';

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->name) && !empty($data->email) && !empty($data->password)) {
    // Sanitize inputs
    $name = $conn->real_escape_string($data->name);
    $email = $conn->real_escape_string($data->email);
    $password = password_hash($data->password, PASSWORD_DEFAULT);
    $role = 'user'; // Default role
    
    // Check if email already exists
    $check_email = "SELECT id FROM users WHERE email = '$email'";
    $result = $conn->query($check_email);
    
    if ($result->num_rows > 0) {
        // Email already exists
        echo json_encode([
            "success" => false,
            "message" => "Email already registered!"
        ]);
    } else {
        // Generate verification code
        $verification_code = rand(100000, 999999);
        $code_expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Insert new user
        $sql = "INSERT INTO users (name, email, password, role, verification_code, code_expires_at, is_verified) 
                VALUES ('$name', '$email', '$password', '$role', '$verification_code', '$code_expires_at', 0)";
        
        if ($conn->query($sql) === TRUE) {
            // Send verification email
            $mail = new PHPMailer(true);
            $email_sent = false;
            
            try {
                // Get SMTP settings from database
                $smtp_settings = getSMTPSettings();
                
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
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Email Verification - Kenya EduHub';
                $mail->Body    = "
                    <p>Thank you for registering with Kenya EduHub!</p>
                    <p>Your verification code is: <strong>$verification_code</strong></p>
                    <p>Please enter this code to verify your account.</p>
                    <p>This code expires in 1 hour.</p>
                ";

                $mail->send();
                $email_sent = true;
            } catch (Exception $e) {
                // Log the error but continue with registration
                error_log("Email verification failed for $email: " . $mail->ErrorInfo);
            }
            
            // Registration successful
            echo json_encode([
                "success" => true,
                "message" => $email_sent 
                    ? "Registration successful. Please check your email for verification code." 
                    : "Registration successful. Please contact support for your verification code.",
                "user" => [
                    "id" => $conn->insert_id,
                    "name" => $name,
                    "email" => $email,
                    "role" => $role
                ]
            ]);
        } else {
            // Registration failed
            echo json_encode([
                "success" => false,
                "message" => "Registration failed. Please try again."
            ]);
        }
    }
} else {
    // Missing required fields
    echo json_encode([
        "success" => false,
        "message" => "Name, email, and password are required"
    ]);
}

$conn->close();
?>