<?php
/**
 * B2C M-Pesa Timeout Callback Handler
 * Marks pending finance-manager withdrawals as failed on B2C timeout.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../config/mpesa_config.php';

// Note: Safaricom doesn't send API keys, so callback authentication is not implemented
// If needed, implement IP whitelisting or other security measures

function storeSafaricomB2CResponse(PDO $pdo, array $response): void
{
    $stmt = $pdo->prepare("INSERT INTO school_b2c_responses (
        withdrawal_id,
        callback_type,
        result_code,
        result_desc,
        originator_conversation_id,
        conversation_id,
        transaction_id,
        transaction_amount,
        receiver_party,
        transaction_completed_at,
        raw_response
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $response['withdrawal_id'] ?? null,
        $response['callback_type'] ?? 'timeout',
        $response['result_code'] ?? null,
        $response['result_desc'] ?? null,
        $response['originator_conversation_id'] ?? null,
        $response['conversation_id'] ?? null,
        $response['transaction_id'] ?? null,
        isset($response['transaction_amount']) && $response['transaction_amount'] !== '' ? (float) $response['transaction_amount'] : null,
        $response['receiver_party'] ?? null,
        $response['transaction_completed_at'] ?? null,
        $response['raw_response'] ?? ''
    ]);
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
    $timeoutCode = $resultCode !== '' ? (string) $resultCode : 'timeout';
    $timeoutDesc = 'B2C transaction timed out' . ($resultDesc ? ': ' . $resultDesc : '.');

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id FROM school_withdrawals WHERE conversation_id = ? OR originator_conversation_id = ? ORDER BY id DESC LIMIT 1 FOR UPDATE");
    $stmt->execute([$conversationId, $originatorConversationId]);
    $withdrawal = $stmt->fetch();

    storeSafaricomB2CResponse($pdo, [
        'withdrawal_id' => $withdrawal['id'] ?? null,
        'callback_type' => 'timeout',
        'result_code' => $timeoutCode,
        'result_desc' => $timeoutDesc,
        'originator_conversation_id' => $originatorConversationId,
        'conversation_id' => $conversationId,
        'raw_response' => $jsonData
    ]);
    
    $stmt = $pdo->prepare("UPDATE school_withdrawals
                           SET status = 'failed',
                               result_code = ?,
                               result_desc = ?,
                               callback_payload = ?
                           WHERE status = 'pending'
                           AND (conversation_id = ? OR originator_conversation_id = ?)");

    $stmt->execute([$timeoutCode, $timeoutDesc, $jsonData, $conversationId, $originatorConversationId]);
    $pdo->commit();
    
    file_put_contents('b2c_callback.log', date('Y-m-d H:i:s') . " - B2C Timeout recorded for ConversationID: $conversationId\n", FILE_APPEND);
    
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
