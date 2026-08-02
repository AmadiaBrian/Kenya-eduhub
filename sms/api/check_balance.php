<?php
// SMS Balance Check API
header('Content-Type: application/json');

// Load config
require_once __DIR__ . '/../../config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get JSON input
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!$data || !isset($data['provider'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$provider = $data['provider'];

try {
    if ($provider === 'mobitech') {
        // Get Mobitech API key from admin settings
        $stmt = $pdo->prepare("SELECT mobitech_api_key FROM admin_sms_settings LIMIT 1");
        $stmt->execute();
        $admin_sms = $stmt->fetch();
        
        if (!$admin_sms || empty($admin_sms['mobitech_api_key'])) {
            echo json_encode(['success' => false, 'error' => 'Mobitech API key not configured']);
            exit;
        }
        
        $api_key = $admin_sms['mobitech_api_key'];
        
        // Call Mobitech balance API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://app.mobitechtechnologies.com/sms/getbalance');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'h_api_key: ' . $api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['response_type' => 'json']));
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo json_encode(['success' => false, 'error' => 'Curl error: ' . $error]);
            exit;
        }
        
        $result = json_decode($response, true);
        
        error_log("Mobitech Balance API Response: " . print_r($result, true));
        
        if (isset($result['credit_balance'])) {
            // Mobitech uses credit_balance field
            echo json_encode(['success' => true, 'balance' => $result['credit_balance']]);
        } elseif (isset($result['balance'])) {
            echo json_encode(['success' => true, 'balance' => $result['balance']]);
        } elseif (isset($result['Balance'])) {
            // Try capitalized version
            echo json_encode(['success' => true, 'balance' => $result['Balance']]);
        } elseif (isset($result['sms_balance'])) {
            // Try alternative field name
            echo json_encode(['success' => true, 'balance' => $result['sms_balance']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid response from Mobitech API', 'raw' => $result]);
        }
        
    } elseif ($provider === 'textsms') {
        // Get Text SMS credentials from admin settings
        $stmt = $pdo->prepare("SELECT textsms_api_key, textsms_partner_id FROM admin_sms_settings LIMIT 1");
        $stmt->execute();
        $admin_sms = $stmt->fetch();
        
        if (!$admin_sms || empty($admin_sms['textsms_api_key'])) {
            echo json_encode(['success' => false, 'error' => 'Text SMS API key not configured']);
            exit;
        }
        
        $api_key = $admin_sms['textsms_api_key'];
        $partner_id = $admin_sms['textsms_partner_id'];
        
        // Call Text SMS balance API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://sms.textsms.co.ke/api/services/getbalance');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'apikey' => $api_key,
            'partnerID' => $partner_id
        ]));
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo json_encode(['success' => false, 'error' => 'Curl error: ' . $error]);
            exit;
        }
        
        $result = json_decode($response, true);
        
        error_log("Text SMS Balance API Response: " . print_r($result, true));
        
        if (isset($result['credit'])) {
            // Text SMS uses credit field
            echo json_encode(['success' => true, 'balance' => $result['credit']]);
        } elseif (isset($result['balance'])) {
            echo json_encode(['success' => true, 'balance' => $result['balance']]);
        } elseif (isset($result['Balance'])) {
            echo json_encode(['success' => true, 'balance' => $result['Balance']]);
        } elseif (isset($result['credit_balance'])) {
            echo json_encode(['success' => true, 'balance' => $result['credit_balance']]);
        } elseif (isset($result['sms_balance'])) {
            echo json_encode(['success' => true, 'balance' => $result['sms_balance']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid response from Text SMS API', 'raw' => $result]);
        }
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid provider']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
