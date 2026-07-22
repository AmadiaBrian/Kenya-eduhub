<?php
// Send Email API
// Handles sending emails to parents using PHPMailer

session_start();
require_once '../../config.php';
require_once '../../PHPMailer/src/PHPMailer.php';
require_once '../../PHPMailer/src/SMTP.php';
require_once '../../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check authentication
if (!isset($_SESSION['school_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$school_id = $_SESSION['school_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';

// Handle POST request to send email
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $recipient = $input['recipient'] ?? '';
    $subject = $input['subject'] ?? '';
    $body = $input['body'] ?? '';
    $recipientId = $input['recipient_id'] ?? '';
    
    if (empty($subject) || empty($body)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Subject and message are required']);
        exit;
    }
    
    try {
        // Get school name and SMTP settings
        $stmt = $pdo->prepare("SELECT school_name FROM schools WHERE id = ?");
        $stmt->execute([$school_id]);
        $school = $stmt->fetch();
        $school_name = $school['school_name'] ?? 'Kenya EduHub';
        
        $stmt = $pdo->prepare("SELECT email, app_password, smtp_host, smtp_port, encryption FROM smtp_settings WHERE school_id = ?");
        $stmt->execute([$school_id]);
        $smtpSettings = $stmt->fetch();
        
        if (!$smtpSettings) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'SMTP settings not configured. Please contact administrator.']);
            exit;
        }
        
        // Get recipient email
        $toEmail = '';
        if ($recipient === 'all') {
            // Get all parent emails for this school
            $stmt = $pdo->prepare("SELECT DISTINCT email FROM parents WHERE school_id = ? AND email IS NOT NULL AND email != ''");
            $stmt->execute([$school_id]);
            $parents = $stmt->fetchAll();
            $toEmail = array_column($parents, 'email');
        } else {
            // Get specific parent email
            $stmt = $pdo->prepare("SELECT email FROM parents WHERE id = ? AND school_id = ?");
            $stmt->execute([$recipientId, $school_id]);
            $parent = $stmt->fetch();
            if ($parent) {
                $toEmail = $parent['email'];
            }
        }
        
        if (empty($toEmail) || (is_array($toEmail) && empty($toEmail))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No valid recipient email found']);
            exit;
        }
        
        // Create PHPMailer instance
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtpSettings['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtpSettings['email'];
        $mail->Password = $smtpSettings['app_password'];
        $mail->SMTPSecure = $smtpSettings['encryption'];
        $mail->Port = $smtpSettings['smtp_port'];
        
        // Recipients
        if (is_array($toEmail)) {
            foreach ($toEmail as $email) {
                $mail->addAddress($email);
            }
        } else {
            $mail->addAddress($toEmail);
        }
        
        // Sender
        $mail->setFrom($smtpSettings['email'], $school_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        // Build email body with header and footer
        $date = date('F j, Y, g:i a');
        $emailBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: #FF6B35; color: white; padding: 20px; text-align: center;'>
                    <h2 style='margin: 0;'>$school_name</h2>
                    <p style='margin: 5px 0 0 0; opacity: 0.9;'>Kenya EduHub</p>
                </div>
                <div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #FF6B35;'>
                    <p style='margin: 0; font-size: 12px; color: #666;'>
                        <strong>From:</strong> $teacher_name<br>
                        <strong>Date:</strong> $date
                    </p>
                </div>
                <div style='padding: 20px; background: white;'>
                    " . nl2br($body) . "
                </div>
                <div style='background: #f1f3f4; padding: 15px; text-align: center; font-size: 12px; color: #666;'>
                    <p style='margin: 0;'>This email was sent from $school_name via Kenya EduHub</p>
                    <p style='margin: 5px 0 0 0;'>&copy; " . date('Y') . " $school_name. All rights reserved.</p>
                </div>
            </div>
        ";
        
        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags($body) . "\n\n---\nFrom: $teacher_name\nDate: $date\nSent from: $school_name via Kenya EduHub";
        
        // Send email
        $mail->send();
        
        echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
        
    } catch (Exception $e) {
        error_log("Email sending error: " . $mail->ErrorInfo);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to send email: ' . $mail->ErrorInfo]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>
