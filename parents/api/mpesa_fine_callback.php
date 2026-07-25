<?php
// M-Pesa Callback Handler for Library Fine Payments (Parents)
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Log the callback for debugging
$json_data = file_get_contents('php://input');
error_log("=== MPESA FINE CALLBACK START (PARENTS) ===");
error_log("M-Pesa Fine Callback Received: " . $json_data);
error_log("Callback URL: " . $_SERVER['REQUEST_URI']);
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));
error_log("GET data: " . print_r($_GET, true));

$data = json_decode($json_data, true);

if (!$data) {
    error_log("Invalid callback data received - JSON decode failed");
    error_log("Raw input length: " . strlen($json_data));
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid data']);
    exit;
}

error_log("JSON decoded successfully");

try {
    // Extract callback data
    $Body = $data['Body'] ?? [];
    $stkCallback = $Body['stkCallback'] ?? [];
    $CallbackMetadata = $stkCallback['CallbackMetadata'] ?? [];
    $MerchantRequestID = $stkCallback['MerchantRequestID'] ?? '';
    $CheckoutRequestID = $stkCallback['CheckoutRequestID'] ?? '';
    $ResultCode = $stkCallback['ResultCode'] ?? 1;
    $ResultDesc = $stkCallback['ResultDesc'] ?? '';
    
    error_log("Callback extracted data: MerchantRequestID=$MerchantRequestID, CheckoutRequestID=$CheckoutRequestID, ResultCode=$ResultCode, ResultDesc=$ResultDesc");
    
    // Extract payment details from metadata
    $Amount = 0;
    $MpesaReceiptNumber = '';
    $TransactionDate = '';
    $PhoneNumber = '';
    
    foreach ($CallbackMetadata['Item'] ?? [] as $item) {
        switch ($item['Name']) {
            case 'Amount':
                $Amount = $item['Value'];
                break;
            case 'MpesaReceiptNumber':
                $MpesaReceiptNumber = $item['Value'];
                break;
            case 'TransactionDate':
                $TransactionDate = $item['Value'];
                break;
            case 'PhoneNumber':
                $PhoneNumber = $item['Value'];
                break;
        }
    }
    
    error_log("M-Pesa Fine Callback Details: ResultCode=$ResultCode, Amount=$Amount, Receipt=$MpesaReceiptNumber, CheckoutRequestID=$CheckoutRequestID");
    
    // Log the transaction in the transactions table before processing
    try {
        $stmt = $pdo->prepare("INSERT INTO transactions (MerchantRequestID, CheckoutRequestID, ResultCode, ResultDesc, Amount, MpesaReceiptNumber, PhoneNumber, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$MerchantRequestID, $CheckoutRequestID, $ResultCode, $ResultDesc, $Amount, $MpesaReceiptNumber, $PhoneNumber]);
        error_log("Transaction logged in transactions table");
    } catch (PDOException $e) {
        error_log("Failed to log transaction: " . $e->getMessage());
        // Continue processing even if transaction logging fails
    }
    
    // Find the fine record using CheckoutRequestID (check regardless of status to handle edge cases)
    $stmt = $pdo->prepare("SELECT * FROM library_fines WHERE transaction_reference = ?");
    $stmt->execute([$CheckoutRequestID]);
    $fine = $stmt->fetch();
    
    if (!$fine) {
        error_log("Fine not found for CheckoutRequestID: $CheckoutRequestID");
        echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Fine not found']);
        exit;
    }
    
    error_log("Found fine ID: {$fine['id']}, Current status: {$fine['status']}, Current amount_paid: {$fine['amount_paid']}, Fine amount: {$fine['amount']}");
    
    if ($ResultCode === 0) {
        // Payment successful - update fine status and school balance
        $new_amount_paid = $fine['amount_paid'] + $Amount;
        $new_status = $new_amount_paid >= $fine['amount'] ? 'paid' : 'partial';
        
        // Update fine record - keep CheckoutRequestID in transaction_reference for status lookup
        $stmt = $pdo->prepare("UPDATE library_fines 
                              SET status = ?, amount_paid = ?, payment_date = NOW(), receipt_number = ? 
                              WHERE id = ?");
        $stmt->execute([$new_status, $new_amount_paid, $MpesaReceiptNumber, $fine['id']]);
        
        // Log the successful payment
        $stmt = $pdo->prepare("INSERT INTO book_history (book_id, school_id, action, user_id, user_type, details) VALUES (?, ?, 'fine_payment_success', 0, 'system', ?)");
        $stmt->execute([$fine['book_id'], $fine['school_id'], "MPESA payment successful: $Amount, Receipt: $MpesaReceiptNumber for fine ID: {$fine['id']}"]);
        
        // Update school balance - THIS IS THE KEY PART
        // Get school_id from library_fines table directly (it has school_id column)
        $school_id = $fine['school_id'];
        
        if ($school_id) {
            // Update school balance
            $stmt = $pdo->prepare("UPDATE school_balances SET balance = balance + ? WHERE school_id = ?");
            $stmt->execute([$Amount, $school_id]);
            
            // Log the school balance update
            error_log("School balance updated: School ID=$school_id, Amount=$Amount, Receipt=$MpesaReceiptNumber");
            
            // Create school withdrawal record for tracking
            $stmt = $pdo->prepare("INSERT INTO school_withdrawals 
                                  (school_id, amount, destination_type, destination_name, destination_account, notes, status, reference_number, created_at) 
                                  VALUES (?, ?, 'library_fine_payment', 'Library Fine Payment', ?, ?, 'completed', ?, NOW())");
            $stmt->execute([
                $school_id, 
                $Amount, 
                $MpesaReceiptNumber, 
                "Fine payment for book ID: {$fine['book_id']}, Fine ID: {$fine['id']}",
                $MpesaReceiptNumber
            ]);
            
            error_log("School withdrawal record created for library fine payment");
        }
        
        error_log("Fine payment processed successfully: Fine ID={$fine['id']}, Amount=$Amount, New Status=$new_status");
        
    } else {
        // Payment failed - revert fine status but keep transaction_reference for status checking
        $stmt = $pdo->prepare("UPDATE library_fines 
                              SET status = 'unpaid', payment_method = NULL 
                              WHERE id = ?");
        $stmt->execute([$fine['id']]);
        
        // Log the failed payment
        $stmt = $pdo->prepare("INSERT INTO book_history (book_id, school_id, action, user_id, user_type, details) VALUES (?, ?, 'fine_payment_failed', 0, 'system', ?)");
        $stmt->execute([$fine['book_id'], $fine['school_id'], "MPESA payment failed: $ResultDesc for fine ID: {$fine['id']}"]);
        
        error_log("Fine payment failed: Fine ID={$fine['id']}, ResultCode=$ResultCode, ResultDesc=$ResultDesc");
    }
    
    // Respond to M-Pesa
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Callback processed successfully']);
    
} catch (PDOException $e) {
    error_log("M-Pesa callback database error: " . $e->getMessage());
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Database error']);
} catch (Exception $e) {
    error_log("M-Pesa callback error: " . $e->getMessage());
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Processing error']);
}
