<?php
// Text SMS Provider Class
class TextSMS {
    private $apiKey;
    private $partnerId;
    private $apiUrl;
    private $senderId;
    
    public function __construct($apiKey = null, $partnerId = null, $senderId = null, $pdo = null, $schoolId = null) {
        // If school-specific credentials are provided, use them
        if ($pdo && $schoolId) {
            try {
                $stmt = $pdo->prepare("SELECT textsms_api_key, textsms_partner_id, textsms_sender_id FROM schools WHERE id = ?");
                $stmt->execute([$schoolId]);
                $school = $stmt->fetch();
                
                if ($school && !empty($school['textsms_api_key'])) {
                    $this->apiKey = $school['textsms_api_key'];
                    $this->partnerId = $school['textsms_partner_id'];
                    $this->senderId = $school['textsms_sender_id'] ?? $senderId ?? TEXTSMS_SENDER_ID;
                } else {
                    // Fallback to global config
                    $this->apiKey = $apiKey ?? TEXTSMS_API_KEY;
                    $this->partnerId = $partnerId ?? null;
                    $this->senderId = $senderId ?? TEXTSMS_SENDER_ID;
                }
            } catch (PDOException $e) {
                error_log("Failed to fetch school SMS credentials: " . $e->getMessage());
                $this->apiKey = $apiKey ?? TEXTSMS_API_KEY;
                $this->partnerId = $partnerId ?? null;
                $this->senderId = $senderId ?? TEXTSMS_SENDER_ID;
            }
        } else {
            // Use provided or global config
            $this->apiKey = $apiKey ?? TEXTSMS_API_KEY;
            $this->partnerId = $partnerId ?? null;
            $this->senderId = $senderId ?? TEXTSMS_SENDER_ID;
        }
        
        $this->apiUrl = 'https://sms.textsms.co.ke/api/services/sendsms/';
    }
    
    /**
     * Send SMS using Text SMS API
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
        
        // Prepare request data according to Text SMS API
        // Use custom encoding to preserve + character
        $data = [
            'apikey' => $this->apiKey,
            'partnerID' => $this->partnerId,
            'mobile' => $phone,
            'message' => $message,
            'shortcode' => $options['sender_id'] ?? $this->senderId
        ];
        
        try {
            // Initialize cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&', PHP_QUERY_RFC3986));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
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
            
            // Check for success in responses array (Text SMS API format)
            if (isset($result['responses']) && is_array($result['responses']) && count($result['responses']) > 0) {
                $firstResponse = $result['responses'][0];
                if (isset($firstResponse['response-code']) && $firstResponse['response-code'] == 200) {
                    return [
                        'success' => true,
                        'message_id' => $firstResponse['messageid'] ?? null,
                        'response' => $result
                    ];
                }
            }
            
            // Check for errors in response
            if (isset($result['response-code']) && $result['response-code'] != 200) {
                return [
                    'success' => false,
                    'error' => $result['response-description'] ?? 'Unknown error',
                    'response' => $result
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Unknown error',
                'response' => $result
            ];
            
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
        // Text SMS balance endpoint is not publicly documented
        // Return a message indicating users should check their account dashboard
        return [
            'success' => false,
            'error' => 'Balance check not available for Text SMS. Please check your balance at https://textsms.co.ke/dashboard',
            'response' => null
        ];
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
