<?php
// M-Pesa Callback Handler - Records to transactions, then updates fee_payments based on transaction status
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/transaction_fees.php';

header("Content-Type: application/json");

// Debug logging
error_log("M-Pesa callback called at " . date('Y-m-d H:i:s'));
error_log("POST data: " . file_get_contents('php://input'));

// Get raw POST data
$raw = file_get_contents('php://input');
$data = json_decode($raw);

// Basic validation
if (!$data || !isset($data->Body->stkCallback)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid callback payload']);
    exit;
}

// Extract core fields
$stkCallback = $data->Body->stkCallback;
$MerchantRequestID = $stkCallback->MerchantRequestID ?? '';
$CheckoutRequestID = $stkCallback->CheckoutRequestID ?? '';
$ResultCode = $stkCallback->ResultCode ?? '';
$ResultDesc = $stkCallback->ResultDesc ?? '';

// Extract metadata
$Amount = 0;
$MpesaReceiptNumber = '';
$TransactionDate = '';
$PhoneNumber = '';

if (isset($stkCallback->CallbackMetadata->Item) && is_array($stkCallback->CallbackMetadata->Item)) {
    foreach ($stkCallback->CallbackMetadata->Item as $item) {
        $name = $item->Name ?? '';
        if ($name === 'Amount') {
            $Amount = $item->Value ?? 0;
        } elseif ($name === 'MpesaReceiptNumber') {
            $MpesaReceiptNumber = $item->Value ?? '';
        } elseif ($name === 'TransactionDate') {
            $TransactionDate = $item->Value ?? '';
        } elseif ($name === 'PhoneNumber') {
            $PhoneNumber = $item->Value ?? '';
        }
    }
}

// Step 1: Record to transactions table in users_db
$checkTx = $pdo->prepare("SELECT ID FROM transactions WHERE CheckoutRequestID = ?");
$checkTx->execute([$CheckoutRequestID]);
$txExists = $checkTx->fetch();

if ($txExists) {
    // Update existing transaction
    $updateTx = $pdo->prepare("UPDATE transactions SET ResultCode = ?, ResultDesc = ?, Amount = ?, MpesaReceiptNumber = ?, PhoneNumber = ? WHERE CheckoutRequestID = ?");
    $updateTx->execute([$ResultCode, $ResultDesc, $Amount, $MpesaReceiptNumber, $PhoneNumber, $CheckoutRequestID]);
    error_log("Updated transaction: CheckoutRequestID=$CheckoutRequestID, ResultCode=$ResultCode");
} else {
    // Insert new transaction
    $insertTx = $pdo->prepare("INSERT INTO transactions (MerchantRequestID, CheckoutRequestID, ResultCode, ResultDesc, Amount, MpesaReceiptNumber, PhoneNumber, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
    $insertTx->execute([$MerchantRequestID, $CheckoutRequestID, $ResultCode, $ResultDesc, $Amount, $MpesaReceiptNumber, $PhoneNumber]);
    error_log("Inserted transaction: CheckoutRequestID=$CheckoutRequestID, ResultCode=$ResultCode");
}

// Step 2: Use CheckoutRequestID to find and update fee_payments table
// Database connection is already handled by config.php ($pdo)

// Find fee payment record by CheckoutRequestID
$checkPayment = $pdo->prepare("SELECT id FROM fee_payments WHERE transaction_id = ?");
$checkPayment->execute([$CheckoutRequestID]);
$feePayment = $checkPayment->fetch();

if ($feePayment) {
    // Payment record found - update or delete based on transaction status
    if ($ResultCode === '0' || $ResultCode === 0) {
        // Transaction successful - update status to completed
        $updatePayment = $pdo->prepare("UPDATE fee_payments SET status = 'completed', receipt_number = ? WHERE transaction_id = ?");
        $updatePayment->execute([$MpesaReceiptNumber, $CheckoutRequestID]);
        error_log("Updated fee payment to completed: CheckoutRequestID=$CheckoutRequestID, Receipt=$MpesaReceiptNumber");
        
        // Update school balance for successful M-Pesa payments
        try {
            // Get student_id and amount from fee_payments
            $getPaymentDetails = $pdo->prepare("SELECT student_id, amount FROM fee_payments WHERE transaction_id = ?");
            $getPaymentDetails->execute([$CheckoutRequestID]);
            $paymentDetails = $getPaymentDetails->fetch();
            
            if ($paymentDetails) {
                $student_id = $paymentDetails['student_id'];
                $payment_amount = $paymentDetails['amount'];
                
                // Get school_id from students table
                $getSchoolId = $pdo->prepare("SELECT school_id FROM students WHERE id = ?");
                $getSchoolId->execute([$student_id]);
                $student = $getSchoolId->fetch();
                
                if ($student) {
                    $school_id = $student['school_id'];
                    
                    // Check if school_balances entry exists
                    $checkBalance = $pdo->prepare("SELECT balance FROM school_balances WHERE school_id = ?");
                    $checkBalance->execute([$school_id]);
                    $balanceEntry = $checkBalance->fetch();
                    
                    if ($balanceEntry) {
                        // Update existing balance
                        $new_balance = $balanceEntry['balance'] + $payment_amount;
                        $updateBalance = $pdo->prepare("UPDATE school_balances SET balance = ?, updated_at = CURRENT_TIMESTAMP WHERE school_id = ?");
                        $updateBalance->execute([$new_balance, $school_id]);
                        error_log("Updated school balance: SchoolID=$school_id, Added=$payment_amount, NewBalance=$new_balance");
                    } else {
                        // Create new balance entry
                        $insertBalance = $pdo->prepare("INSERT INTO school_balances (school_id, balance) VALUES (?, ?)");
                        $insertBalance->execute([$school_id, $payment_amount]);
                        $new_balance = $payment_amount;
                        error_log("Created school balance: SchoolID=$school_id, Balance=$payment_amount");
                    }
                    
                    // Send SMS notification to school about fee payment
                    try {
                        require_once __DIR__ . '/../../sms/sms_config.php';
                        require_once __DIR__ . '/../../sms/MobitechSMS.php';
                        require_once __DIR__ . '/../../sms/TextSMS.php';
                        
                        // Get school phone and name
                        $getSchool = $pdo->prepare("SELECT phone, school_name FROM schools WHERE id = ?");
                        $getSchool->execute([$school_id]);
                        $school = $getSchool->fetch();
                        
                        // Get parent information
                        $getParent = $pdo->prepare("SELECT p.first_name, p.last_name, p.phone FROM parents p JOIN student_parents sp ON p.id = sp.parent_id JOIN students s ON s.id = sp.student_id WHERE s.id = ?");
                        $getParent->execute([$student_id]);
                        $parent = $getParent->fetch();
                        
                        // Get fee payment details (fee type, term, year)
                        $getFeeDetails = $pdo->prepare("SELECT fee_type, term, year FROM fee_payments WHERE transaction_id = ?");
                        $getFeeDetails->execute([$CheckoutRequestID]);
                        $feeDetails = $getFeeDetails->fetch();
                        
                        if ($school && !empty($school['phone'])) {
                            // Get admin SMS settings
                            $getSmsSettings = $pdo->prepare("SELECT * FROM admin_sms_settings LIMIT 1");
                            $getSmsSettings->execute();
                            $admin_sms = $getSmsSettings->fetch();
                            
                            if ($admin_sms && !empty($admin_sms['sms_enabled'])) {
                                $parent_name = $parent ? ($parent['first_name'] . ' ' . $parent['last_name']) : 'Parent';
                                $fee_type = $feeDetails['fee_type'] ?? 'School Fees';
                                $term = $feeDetails['term'] ?? 'Current Term';
                                $year = $feeDetails['year'] ?? date('Y');
                                
                                // Create SMS message in M-Pesa format
                                $sms_message = $MpesaReceiptNumber . " Confirmed. KES " . number_format($payment_amount, 2) . " received from $parent_name for $fee_type ($term $year) on " . date('d/m/y') . " at " . date('h:i A') . ". New school balance is KES " . number_format($new_balance, 2) . ".";
                                
                                // Send SMS using configured provider
                                if ($admin_sms['sms_provider'] === 'mobitech') {
                                    $mobitech = new MobitechSMS($admin_sms['mobitech_api_key'], $admin_sms['mobitech_sender_id']);
                                    $result = $mobitech->sendSMS($school['phone'], $sms_message);
                                    error_log("SMS sent via Mobitech: " . print_r($result, true));
                                } elseif ($admin_sms['sms_provider'] === 'textsms') {
                                    $textsms = new TextSMS($admin_sms['textsms_api_key'], $admin_sms['textsms_partner_id'], $admin_sms['textsms_sender_id']);
                                    $result = $textsms->sendSMS($school['phone'], $sms_message);
                                    error_log("SMS sent via Text SMS: " . print_r($result, true));
                                }
                            }
                        }
                    } catch (Exception $sms_error) {
                        error_log("Failed to send fee payment SMS: " . $sms_error->getMessage());
                        // Don't fail the callback if SMS fails
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Failed to update school balance: " . $e->getMessage());
        }
    } else {
        // Transaction failed - delete the fee payment entry
        $deletePayment = $pdo->prepare("DELETE FROM fee_payments WHERE transaction_id = ?");
        $deletePayment->execute([$CheckoutRequestID]);
        error_log("Deleted failed fee payment: CheckoutRequestID=$CheckoutRequestID, ResultCode=$ResultCode");
    }
} else {
    // No fee payment found with this CheckoutRequestID
    // Try to find by AccountReference (for backward compatibility)
    $AccountReference = $stkCallback->AccountReference ?? '';
    if (!empty($AccountReference) && strpos($AccountReference, 'FEE:') === 0) {
        if (preg_match('/FEE:(\d+)\|STUDENT:(\d+)\|AMOUNT:(\d+)\|TERM:([^|]+)\|YEAR:(\d+)\|TYPE:([^|]+)/', $AccountReference, $matches)) {
            $payment_id = (int)$matches[1];
            
            if ($ResultCode === '0' || $ResultCode === 0) {
                // Transaction successful - update status to completed
                $updatePayment = $pdo->prepare("UPDATE fee_payments SET transaction_id = ?, status = 'completed', receipt_number = ? WHERE id = ?");
                $updatePayment->execute([$CheckoutRequestID, $MpesaReceiptNumber, $payment_id]);
                error_log("Updated fee payment by ID to completed: PaymentID=$payment_id, CheckoutRequestID=$CheckoutRequestID");
            } else {
                // Transaction failed - delete the fee payment entry
                $deletePayment = $pdo->prepare("DELETE FROM fee_payments WHERE id = ?");
                $deletePayment->execute([$payment_id]);
                error_log("Deleted failed fee payment by ID: PaymentID=$payment_id, ResultCode=$ResultCode");
            }
        }
    }
}

echo json_encode([
    'success' => true,
    'message' => $ResultCode === '0' ? 'Payment recorded successfully' : 'Payment recorded',
    'result_code' => $ResultCode,
    'amount' => $Amount,
    'receipt' => $MpesaReceiptNumber
]);
