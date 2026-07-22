<?php
// SMTP Settings API
// Handles CRUD operations for Gmail SMTP settings

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

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            // Get SMTP settings for current school
            $stmt = $pdo->prepare("SELECT id, school_id, email, app_password, smtp_host, smtp_port, encryption, created_at, updated_at 
                                   FROM smtp_settings 
                                   WHERE school_id = ?");
            $stmt->execute([$school_id]);
            $settings = $stmt->fetch();
            
            if ($settings) {
                echo json_encode(['success' => true, 'data' => $settings]);
            } else {
                echo json_encode(['success' => true, 'data' => null]);
            }
            break;
            
        case 'POST':
            // Save or update SMTP settings
            if ($action === 'test') {
                // Test SMTP connection
                error_log("SMTP Test: Starting connection test");
                $input = json_decode(file_get_contents('php://input'), true);
                $email = $input['email'] ?? '';
                $appPassword = $input['app_password'] ?? '';
                
                error_log("SMTP Test: Email = " . $email);
                error_log("SMTP Test: Password length = " . strlen($appPassword));
                
                if (empty($email) || empty($appPassword)) {
                    error_log("SMTP Test: Missing email or password");
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Email and app password are required']);
                    exit;
                }
                
                // Basic validation test
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    error_log("SMTP Test: Invalid email format");
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
                    exit;
                }
                
                error_log("SMTP Test: Email validated, attempting SMTP connection");
                
                // Test actual SMTP connection using PHPMailer
                $mail = new PHPMailer(true);
                
                try {
                    // Server settings
                    error_log("SMTP Test: Configuring SMTP settings");
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = $email;
                    $mail->Password = $appPassword;
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;
                    $mail->Timeout = 10; // 10 second timeout
                    
                    error_log("SMTP Test: Attempting to connect to smtp.gmail.com:587");
                    
                    // Test connection
                    $mail->SMTPDebug = SMTP::DEBUG_OFF; // Disable debug output
                    $mail->smtpConnect();
                    
                    error_log("SMTP Test: Connection successful, closing connection");
                    $mail->smtpClose();
                    
                    error_log("SMTP Test: Test completed successfully");
                    echo json_encode(['success' => true, 'message' => 'SMTP connection test successful!']);
                } catch (Exception $e) {
                    error_log("SMTP Test: Connection failed - " . $mail->ErrorInfo);
                    echo json_encode(['success' => false, 'error' => 'SMTP connection failed: ' . $mail->ErrorInfo]);
                }
            } else {
                // Save SMTP settings
                $input = json_decode(file_get_contents('php://input'), true);
                $email = $input['email'] ?? '';
                $appPassword = $input['app_password'] ?? '';
                $smtpHost = $input['smtp_host'] ?? 'smtp.gmail.com';
                $smtpPort = $input['smtp_port'] ?? 587;
                $encryption = $input['encryption'] ?? 'tls';
                
                if (empty($email) || empty($appPassword)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Email and app password are required']);
                    exit;
                }
                
                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
                    exit;
                }
                
                // Check if settings already exist for this school
                $stmt = $pdo->prepare("SELECT id FROM smtp_settings WHERE school_id = ?");
                $stmt->execute([$school_id]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Update existing settings
                    $stmt = $pdo->prepare("UPDATE smtp_settings 
                                           SET email = ?, app_password = ?, smtp_host = ?, smtp_port = ?, encryption = ?, updated_at = CURRENT_TIMESTAMP 
                                           WHERE school_id = ?");
                    $stmt->execute([$email, $appPassword, $smtpHost, $smtpPort, $encryption, $school_id]);
                } else {
                    // Insert new settings
                    $stmt = $pdo->prepare("INSERT INTO smtp_settings (school_id, email, app_password, smtp_host, smtp_port, encryption) 
                                           VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$school_id, $email, $appPassword, $smtpHost, $smtpPort, $encryption]);
                }
                
                echo json_encode(['success' => true, 'message' => 'SMTP settings saved successfully']);
            }
            break;
            
        case 'DELETE':
            // Delete SMTP settings
            $stmt = $pdo->prepare("DELETE FROM smtp_settings WHERE school_id = ?");
            $stmt->execute([$school_id]);
            
            echo json_encode(['success' => true, 'message' => 'SMTP settings deleted successfully']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
} catch (PDOException $e) {
    error_log("SMTP Settings API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
?>
