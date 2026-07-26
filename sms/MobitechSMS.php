<?php
// Mobitech SMS Provider Class
class MobitechSMS {
    private $apiKey;
    private $apiUrl;
    private $senderId;
    
    public function __construct($apiKey = null, $senderId = null, $pdo = null, $schoolId = null) {
        // If school-specific credentials are provided, use them
        if ($pdo && $schoolId) {
            try {
                $stmt = $pdo->prepare("SELECT mobitech_api_key, mobitech_sender_id FROM schools WHERE id = ?");
                $stmt->execute([$schoolId]);
                $school = $stmt->fetch();
                
                if ($school && !empty($school['mobitech_api_key'])) {
                    $this->apiKey = $school['mobitech_api_key'];
                    $this->senderId = $school['mobitech_sender_id'] ?? $senderId ?? MOBITECH_SENDER_ID;
                } else {
                    // Fallback to global config
                    $this->apiKey = $apiKey ?? MOBITECH_API_KEY;
                    $this->senderId = $senderId ?? MOBITECH_SENDER_ID;
                }
            } catch (PDOException $e) {
                error_log("Failed to fetch school SMS credentials: " . $e->getMessage());
                $this->apiKey = $apiKey ?? MOBITECH_API_KEY;
                $this->senderId = $senderId ?? MOBITECH_SENDER_ID;
            }
        } else {
            // Use provided or global config
            $this->apiKey = $apiKey ?? MOBITECH_API_KEY;
            $this->senderId = $senderId ?? MOBITECH_SENDER_ID;
        }
        
        $this->apiUrl = MOBITECH_API_URL;
    }
    
    /**
     * Send SMS using Mobitech API
     * @param string $phone - Recipient phone number (format: 2547XXXXXXXX)
     * @param string $message - SMS message content
     * @param array $options - Additional options (sender_id, etc.)
     * @return array - Response with status and message
     */
    public function sendSMS($phone, $message, $options = []) {
        // Validate phone number
        $phone = $this->formatPhoneNumber($phone);
        if (!$phone) {
            return [
                'success' => false,
                'error' => 'Invalid phone number format'
            ];
        }
        
        // Prepare request data according to Mobitech API
        $data = [
            'mobile' => $phone,
            'response_type' => 'json',
            'sender_name' => $options['sender_id'] ?? $this->senderId,
            'service_id' => 0,
            'message' => $message
        ];
        
        try {
            // Initialize cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, MOBITECH_API_URL);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'h_api_key: ' . $this->apiKey,
                'Accept: application/json'
            ]);
            
            // Execute request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return [
                    'success' => false,
                    'error' => 'cURL Error: ' . $error
                ];
            }
            
            // Parse response
            $result = json_decode($response, true);
            
            if ($httpCode === 200) {
                return [
                    'success' => true,
                    'message_id' => $result['message_id'] ?? null,
                    'response' => $result,
                    'cost' => $result['cost'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $result['message'] ?? 'Unknown error',
                    'response' => $result
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send bulk SMS
     * @param array $recipients - Array of phone numbers
     * @param string $message - SMS message content
     * @param array $options - Additional options
     * @return array - Response with status and results
     */
    public function sendBulkSMS($recipients, $message, $options = []) {
        $results = [];
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($recipients as $phone) {
            $result = $this->sendSMS($phone, $message, $options);
            $results[] = [
                'phone' => $phone,
                'result' => $result
            ];
            
            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
            
            // Small delay to avoid rate limiting
            usleep(100000); // 0.1 second
        }
        
        return [
            'success' => $failureCount === 0,
            'total' => count($recipients),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results
        ];
    }
    
    /**
     * Check SMS balance
     * @return array - Balance information
     */
    public function checkBalance() {
        try {
            // Try different balance endpoints
            $endpoints = [
                'https://app.mobitechtechnologies.com/api/sms/balance',
                'https://app.mobitechtechnologies.com/sms/balance',
                'https://app.mobitechtechnologies.com/api/credits',
                'https://app.mobitechtechnologies.com/credits'
            ];
            
            foreach ($endpoints as $endpoint) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'h_api_key: ' . $this->apiKey,
                    'Accept: application/json'
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                
                error_log("Mobitech Balance Check - Endpoint: $endpoint, HTTP Code: $httpCode, Response: $response, Error: $error");
                
                if ($httpCode === 200 && !$error) {
                    $result = json_decode($response, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return [
                            'success' => true,
                            'balance' => $result['balance'] ?? $result['credits'] ?? $result['units'] ?? $result['credit_balance'] ?? 0,
                            'response' => $result
                        ];
                    }
                }
            }
            
            return [
                'success' => false,
                'error' => 'Balance endpoint not found or not accessible'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Format phone number to Kenyan format
     * @param string $phone - Phone number
     * @return string|null - Formatted phone number or null if invalid
     */
    private function formatPhoneNumber($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Check if it's a valid Kenyan number
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            return '254' . substr($phone, 1);
        } elseif (strlen($phone) === 12 && substr($phone, 0, 3) === '254') {
            return $phone;
        } elseif (strlen($phone) === 9 && substr($phone, 0, 1) === '7') {
            return '254' . $phone;
        }
        
        return null;
    }
}
?>
