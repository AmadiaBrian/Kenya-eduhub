<?php
// Check B2C Withdrawal Status
session_start();
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Check if finance manager is logged in
if (!isset($_SESSION['finance_manager_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $withdrawal_id = trim($_POST['withdrawal_id'] ?? '');
    
    if ($withdrawal_id === '') {
        throw new Exception('Missing withdrawal ID');
    }

    // Check school_withdrawals table
    $stmt = $pdo->prepare("SELECT * FROM school_withdrawals WHERE id = ? LIMIT 1");
    $stmt->execute([$withdrawal_id]);
    $withdrawal = $stmt->fetch();

    if (!$withdrawal) {
        echo json_encode([
            'success' => true,
            'found' => false,
            'status' => 'not_found',
            'message' => 'Withdrawal not found'
        ]);
        exit;
    }

    // Determine status from school_withdrawals table
    $status = $withdrawal['status'] ?? 'pending';
    $mpesaReceipt = $withdrawal['mpesa_receipt_number'] ?? '';
    $amount = $withdrawal['amount'] ?? 0;
    $resultDesc = $withdrawal['result_desc'] ?? '';
    $resultCode = $withdrawal['result_code'] ?? '';

    // Map status to response format
    $responseStatus = 'pending';
    $errorMessage = '';

    if ($status === 'success') {
        $responseStatus = 'success';
    } elseif ($status === 'failed') {
        $responseStatus = 'failed';
        $errorMessage = $resultDesc ?: 'Withdrawal failed';
    }

    $response = [
        'success' => true,
        'found' => true,
        'status' => $responseStatus,
        'error_message' => $errorMessage,
        'mpesa_receipt' => $mpesaReceipt,
        'amount' => $amount,
        'result_code' => $resultCode,
        'reference_number' => $withdrawal['reference_number'] ?? ''
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Check B2C status error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
