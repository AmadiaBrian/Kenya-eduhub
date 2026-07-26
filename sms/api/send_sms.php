<?php
// SMS Send API Endpoint
header('Content-Type: application/json');

// Load dependencies
require_once '../../config.php';
require_once '../sms_config.php';
require_once '../SMSHelper.php';

// Check if SMS is enabled
if (!SMS_ENABLED) {
    echo json_encode([
        'success' => false,
        'error' => 'SMS service is disabled'
    ]);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request data'
    ]);
    exit;
}

// Validate required fields
$required = ['phone', 'message'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode([
            'success' => false,
            'error' => "Missing required field: $field"
        ]);
        exit;
    }
}

// Get parameters
$phone = $input['phone'];
$message = $input['message'];
$provider = $input['provider'] ?? null; // Optional - will use school's default
$senderId = $input['sender_id'] ?? null;
$schoolId = $input['school_id'] ?? null;
$recipientName = $input['recipient_name'] ?? null;
$recipientType = $input['recipient_type'] ?? 'parent';
$messageType = $input['message_type'] ?? 'general';

// Initialize SMS Helper with school's selected provider
try {
    $smsHelper = new SMSHelper($pdo, $schoolId);
    
    // Build options
    $options = [
        'recipient_name' => $recipientName,
        'recipient_type' => $recipientType,
        'message_type' => $messageType
    ];
    
    // Use provided provider if specified, otherwise use school's default
    if ($provider) {
        $options['provider'] = $provider;
    }
    
    if ($senderId) {
        $options['sender_id'] = $senderId;
    }
    
    // Send SMS
    $result = $smsHelper->sendSMS($phone, $message, $schoolId, $options);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Exception: ' . $e->getMessage()
    ]);
}
?>
