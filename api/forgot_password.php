<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Include PHPMailer
require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../config.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = isset($data['email']) ? trim($data['email']) : '';

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // For security reasons, we don't reveal if the email exists or not
        echo json_encode(['success' => true, 'message' => 'If your email exists in our system, you will receive password reset instructions shortly.']);
        exit;
    }

    // Generate a 6-digit code
    $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Store the reset code in the database
    $stmt = $pdo->prepare("UPDATE users SET reset_code = ?, reset_expires_at = ? WHERE email = ?");
    $stmt->execute([$code, $expires, $email]);

    // Send email with PHPMailer
    $mail = new PHPMailer(true);
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
        $mail->Subject = 'Password Reset Code - Kenya EduHub';
        $mail->Body    = "
            <p>You requested a password reset.</p>
            <p>Your verification code is: <strong>$code</strong></p>
            <p>Code expires in 10 minutes.</p>
        ";

        $mail->send();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Password reset code sent to your email.'
        ]);
    } catch (Exception $e) {
        // Even if email fails, we still return success for security reasons
        echo json_encode([
            'success' => true, 
            'message' => 'Password reset code sent to your email.'
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>