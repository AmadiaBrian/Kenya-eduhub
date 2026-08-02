<?php
/**
 * B2C M-Pesa Result Callback Handler
 * Deducts the school balance only after Safaricom confirms successful B2C.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../config/mpesa_config.php';

// Debug logging
error_log("B2C callback called at " . date('Y-m-d H:i:s'));
error_log("POST data: " . file_get_contents('php://input'));

// Note: Safaricom doesn't send API keys, so callback authentication is not implemented
// If needed, implement IP whitelisting or other security measures

function getB2CResultParameter(array $result, string $key): ?string
{
    $parameters = $result['ResultParameters']['ResultParameter'] ?? $result['UtilityCallbackData']['ResultParameter'] ?? [];

    foreach ($parameters as $parameter) {
        if (($parameter['Key'] ?? '') === $key) {
            return isset($parameter['Value']) ? (string) $parameter['Value'] : null;
        }
    }

    return null;
}

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
        $response['callback_type'] ?? 'result',
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

// Log the callback for debugging
file_put_contents('b2c_callback.log', date('Y-m-d H:i:s') . " - B2C Result: " . $jsonData . "\n", FILE_APPEND);

try {
    // Extract B2C result data
    $resultCode = $data['Result']['ResultCode'] ?? '';
    $resultDesc = $data['Result']['ResultDesc'] ?? '';
    $originatorConversationId = $data['Result']['OriginatorConversationID'] ?? '';
    $conversationId = $data['Result']['ConversationID'] ?? '';
    $transactionId = $data['Result']['TransactionID'] ?? getB2CResultParameter($data['Result'] ?? [], 'TransactionReceipt');
    $amount = getB2CResultParameter($data['Result'] ?? [], 'TransactionAmount');
    $receiverParty = getB2CResultParameter($data['Result'] ?? [], 'ReceiverPartyPublicName');
    $transactionTime = getB2CResultParameter($data['Result'] ?? [], 'TransactionCompletedDateTime');
    
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM school_withdrawals WHERE conversation_id = ? OR originator_conversation_id = ? ORDER BY id DESC LIMIT 1 FOR UPDATE");
    $stmt->execute([$conversationId, $originatorConversationId]);
    $withdrawal = $stmt->fetch();

    storeSafaricomB2CResponse($pdo, [
        'withdrawal_id' => $withdrawal['id'] ?? null,
        'callback_type' => 'result',
        'result_code' => (string) $resultCode,
        'result_desc' => $resultDesc,
        'originator_conversation_id' => $originatorConversationId,
        'conversation_id' => $conversationId,
        'transaction_id' => $transactionId,
        'transaction_amount' => $amount,
        'receiver_party' => $receiverParty,
        'transaction_completed_at' => $transactionTime,
        'raw_response' => $jsonData
    ]);

    if (!$withdrawal) {
        $pdo->commit();
        file_put_contents('b2c_callback.log', date('Y-m-d H:i:s') . " - B2C Result withdrawal not found. ConversationID: $conversationId, OriginatorConversationID: $originatorConversationId\n", FILE_APPEND);
    } elseif ((string) $resultCode === '0') {
        if ($withdrawal['status'] !== 'completed' && empty($withdrawal['balance_deducted_at'])) {
            $stmt = $pdo->prepare("UPDATE school_balances SET balance = balance - ?, updated_at = CURRENT_TIMESTAMP WHERE school_id = ?");
            $stmt->execute([(float) $withdrawal['amount'], $withdrawal['school_id']]);
        }

        $stmt = $pdo->prepare("UPDATE school_withdrawals SET status = 'completed', result_code = ?, result_desc = ?, mpesa_receipt_number = ?, success_at = COALESCE(success_at, NOW()), balance_deducted_at = COALESCE(balance_deducted_at, NOW()), callback_payload = ? WHERE id = ?");
        $stmt->execute([
            (string) $resultCode,
            $resultDesc ?: 'B2C payment completed successfully.',
            $transactionId,
            $jsonData,
            $withdrawal['id']
        ]);

        $pdo->commit();
        file_put_contents('b2c_callback.log', date('Y-m-d H:i:s') . " - B2C Success: Ksh {$withdrawal['amount']} sent to $receiverParty, TransactionID: $transactionId, WithdrawalID: {$withdrawal['id']}\n", FILE_APPEND);
        
        // Send SMS notification to school phone
        try {
            require_once __DIR__ . '/../../sms/sms_config.php';
            require_once __DIR__ . '/../../sms/MobitechSMS.php';
            require_once __DIR__ . '/../../sms/TextSMS.php';
            
            // Get school phone number
            $stmt = $pdo->prepare("SELECT phone, school_name FROM schools WHERE id = ?");
            $stmt->execute([$withdrawal['school_id']]);
            $school = $stmt->fetch();
            
            if ($school && !empty($school['phone'])) {
                // Get admin SMS settings
                $stmt = $pdo->prepare("SELECT * FROM admin_sms_settings LIMIT 1");
                $stmt->execute();
                $admin_sms = $stmt->fetch();
                
                if ($admin_sms && !empty($admin_sms['sms_enabled'])) {
                    $phone = $school['phone'];
                    $reference_number = $withdrawal['reference_number'];
                    $amount = $withdrawal['amount'];
                    
                    // Get new balance
                    $stmt = $pdo->prepare("SELECT balance FROM school_balances WHERE school_id = ?");
                    $stmt->execute([$withdrawal['school_id']]);
                    $new_balance = $stmt->fetchColumn();
                    
                    // Create M-Pesa style SMS message
                    $sms_message = $reference_number . " Confirmed. KES " . number_format($amount, 2) . " withdrawn from Kenya EduHub on " . date('d/m/y') . " at " . date('h:i A') . ". New balance is KES " . number_format($new_balance, 2) . ".";
                    
                    // Send SMS using configured provider
                    if ($admin_sms['sms_provider'] === 'mobitech') {
                        $mobitech = new MobitechSMS($admin_sms['mobitech_api_key'], $admin_sms['mobitech_sender_id']);
                        $result = $mobitech->sendSMS($phone, $sms_message);
                        file_put_contents('b2c_callback.log', date('Y-m-d H:i:s') . " - SMS sent via Mobitech: " . print_r($result, true) . "\n", FILE_APPEND);
                    } elseif ($admin_sms['sms_provider'] === 'textsms') {
                        $textsms = new TextSMS($admin_sms['textsms_api_key'], $admin_sms['textsms_partner_id'], $admin_sms['textsms_sender_id']);
                        $result = $textsms->sendSMS($phone, $sms_message);
                        file_put_contents('b2c_callback.log', date('Y-m-d H:i:s') . " - SMS sent via Text SMS: " . print_r($result, true) . "\n", FILE_APPEND);
                    }
                }
            }
        } catch (Exception $sms_error) {
            file_put_contents('b2c_callback.log', date('Y-m-d H:i:s') . " - Failed to send withdrawal SMS: " . $sms_error->getMessage() . "\n", FILE_APPEND);
            // Don't fail the callback if SMS fails
        }
    } else {
        $stmt = $pdo->prepare("UPDATE school_withdrawals SET status = 'failed', result_code = ?, result_desc = ?, callback_payload = ? WHERE id = ?");
        $stmt->execute([(string) $resultCode, $resultDesc ?: 'B2C payment failed.', $jsonData, $withdrawal['id']]);

        $pdo->commit();
        file_put_contents('b2c_callback.log', date('Y-m-d H:i:s') . " - B2C Failed: $resultDesc, WithdrawalID: {$withdrawal['id']}\n", FILE_APPEND);
    }
    
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
    file_put_contents('b2c_callback.log', date('Y-m-d H:i:s') . " - B2C Error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    echo json_encode([
        'ResponseCode' => '1',
        'ResponseDescription' => 'Error processing callback'
    ]);
}
?>
