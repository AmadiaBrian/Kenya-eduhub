<?php
/**
 * B2C M-Pesa Timeout Callback Handler for Schools
 * Handles timeout callbacks from B2C transactions
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../../config.php';

// Load M-Pesa configuration for callback authentication
$mpesa_config = require __DIR__ . '/../../config/mpesa_config.php';

// Optional: Validate callback API key for additional security
$callback_api_key = $mpesa_config['callback_api_key'] ?? '';
if ($callback_api_key !== '' && $callback_api_key !== 'your_secure_api_key_here') {
    $provided_key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
    if ($provided_key !== $callback_api_key) {
        http_response_code(401);
        echo json_encode(['ResponseCode' => '1', 'ResponseDescription' => 'Unauthorized']);
        exit;
    }
}

// Get the JSON response from M-Pesa
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Log the timeout for debugging
file_put_contents('b2c_callback.log', date('Y-m-d H:i:s') . " - B2C Timeout: " . $jsonData . "\n", FILE_APPEND);

try {
    // Extract timeout data
    $resultCode = $data['Result']['ResultCode'] ?? '';
    $resultDesc = $data['Result']['ResultDesc'] ?? '';
    $originatorConversationId = $data['Result']['OriginatorConversationID'] ?? '';
    $conversationId = $data['Result']['ConversationID'] ?? '';
    
    $pdo->beginTransaction();

    // Find the withdrawal by ConversationID or OriginatorConversationID
    $stmt = $pdo->prepare("SELECT * FROM school_withdrawals WHERE conversation_id = ? OR originator_conversation_id = ? ORDER BY id DESC LIMIT 1 FOR UPDATE");
    $stmt->execute([$conversationId, $originatorConversationId]);
    $withdrawal = $stmt->fetch();

    if ($withdrawal) {
        // Update withdrawal as timed out
        $stmt = $pdo->prepare("UPDATE school_withdrawals SET status = 'failed', result_code = ?, result_desc = ?, callback_payload = ? WHERE id = ?");
        $timeoutCode = 'timeout';
        $timeoutDesc = 'Transaction timed out: ' . $resultDesc;
        
        $stmt->execute([$timeoutCode, $timeoutDesc, $jsonData, $withdrawal['id']]);
        
        file_put_contents('b2c_callback.log', date('Y-m-d H:i:s') . " - B2C Timeout recorded for WithdrawalID: {$withdrawal['id']}, ConversationID: $conversationId\n", FILE_APPEND);
    } else {
        file_put_contents('b2c_callback.log', date('Y-m-d H:i:s') . " - B2C Timeout withdrawal not found. ConversationID: $conversationId, OriginatorConversationID: $originatorConversationId\n", FILE_APPEND);
    }

    $pdo->commit();
    
    // Respond to M-Pesa
    echo json_encode([
        'ResponseCode' => '0',
        'ResponseDescription' => 'Success'
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log error
    file_put_contents('b2c_callback.log', date('Y-m-d H:i:s') . " - B2C Timeout Error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    echo json_encode([
        'ResponseCode' => '1',
        'ResponseDescription' => 'Error processing timeout'
    ]);
}
?>
