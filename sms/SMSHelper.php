<?php
// SMS Helper Class for managing SMS operations
require_once 'sms_config.php';
require_once 'MobitechSMS.php';
require_once 'TextSMS.php';

class SMSHelper {
    private $pdo;
    private $defaultProvider;
    
    public function __construct($pdo, $schoolId = null, $defaultProvider = null) {
        $this->pdo = $pdo;
        
        // If schoolId is provided, fetch their selected provider from database
        if ($schoolId) {
            try {
                $stmt = $pdo->prepare("SELECT sms_provider FROM schools WHERE id = ?");
                $stmt->execute([$schoolId]);
                $school = $stmt->fetch();
                if ($school && !empty($school['sms_provider'])) {
                    $this->defaultProvider = $school['sms_provider'];
                } else {
                    $this->defaultProvider = $defaultProvider ?? DEFAULT_SMS_PROVIDER;
                }
            } catch (PDOException $e) {
                error_log("Failed to fetch school SMS provider: " . $e->getMessage());
                $this->defaultProvider = $defaultProvider ?? DEFAULT_SMS_PROVIDER;
            }
        } else {
            $this->defaultProvider = $defaultProvider ?? DEFAULT_SMS_PROVIDER;
        }
    }
    
    /**
     * Send SMS with automatic logging
     * @param string $phone - Recipient phone number
     * @param string $message - SMS message
     * @param int $schoolId - School ID
     * @param array $options - Additional options
     * @return array - Result
     */
    public function sendSMS($phone, $message, $schoolId, $options = []) {
        $provider = $options['provider'] ?? $this->defaultProvider;
        $senderId = $options['sender_id'] ?? null;
        $recipientName = $options['recipient_name'] ?? null;
        $recipientType = $options['recipient_type'] ?? 'parent';
        $messageType = $options['message_type'] ?? 'general';
        
        // Initialize provider with school-specific credentials
        if ($provider === 'mobitech') {
            $sms = new MobitechSMS(null, $senderId, $this->pdo, $schoolId);
        } else {
            // Get partner ID and sender ID from database for Text SMS
            try {
                $stmt = $this->pdo->prepare("SELECT textsms_partner_id, textsms_sender_id FROM schools WHERE id = ?");
                $stmt->execute([$schoolId]);
                $school = $stmt->fetch();
                $partnerId = $school['textsms_partner_id'] ?? null;
                $providerSenderId = $school['textsms_sender_id'] ?? null;
            } catch (PDOException $e) {
                $partnerId = null;
                $providerSenderId = null;
            }
            $sms = new TextSMS(null, $partnerId, $senderId ?? $providerSenderId, $this->pdo, $schoolId);
        }
        
        // Send SMS
        $result = $sms->sendSMS($phone, $message, ['sender_id' => $senderId]);
        
        // Log SMS
        if (SMS_LOGGING) {
            $this->logSMS([
                'school_id' => $schoolId,
                'recipient_phone' => $phone,
                'recipient_name' => $recipientName,
                'recipient_type' => $recipientType,
                'message' => $message,
                'message_type' => $messageType,
                'provider' => $provider,
                'sender_id' => $senderId,
                'status' => $result['success'] ? 'sent' : 'failed',
                'api_response' => json_encode($result['response'] ?? []),
                'message_id' => $result['message_id'] ?? null,
                'cost' => $result['cost'] ?? null
            ]);
        }
        
        return $result;
    }
    
    /**
     * Send SMS using template
     * @param string $template - Template key
     * @param array $data - Data for template replacement
     * @param string $phone - Recipient phone
     * @param int $schoolId - School ID
     * @param array $options - Additional options
     * @return array - Result
     */
    public function sendTemplateSMS($template, $data, $phone, $schoolId, $options = []) {
        $message = $this->getTemplateMessage($template, $data);
        $options['message_type'] = $template;
        return $this->sendSMS($phone, $message, $schoolId, $options);
    }
    
    /**
     * Send bulk SMS to multiple recipients
     * @param array $recipients - Array of ['phone' => '...', 'name' => '...']
     * @param string $message - SMS message
     * @param int $schoolId - School ID
     * @param array $options - Additional options
     * @return array - Result
     */
    public function sendBulkSMS($recipients, $message, $schoolId, $options = []) {
        $provider = $options['provider'] ?? $this->defaultProvider;
        $senderId = $options['sender_id'] ?? null;
        $recipientType = $options['recipient_type'] ?? 'parent';
        $messageType = $options['message_type'] ?? 'bulk';
        
        // Initialize provider with school-specific credentials
        if ($provider === 'mobitech') {
            $sms = new MobitechSMS(null, $senderId, $this->pdo, $schoolId);
        } else {
            // Get partner ID and sender ID from database for Text SMS
            try {
                $stmt = $this->pdo->prepare("SELECT textsms_partner_id, textsms_sender_id FROM schools WHERE id = ?");
                $stmt->execute([$schoolId]);
                $school = $stmt->fetch();
                $partnerId = $school['textsms_partner_id'] ?? null;
                $providerSenderId = $school['textsms_sender_id'] ?? null;
            } catch (PDOException $e) {
                $partnerId = null;
                $providerSenderId = null;
            }
            $sms = new TextSMS(null, $partnerId, $senderId ?? $providerSenderId, $this->pdo, $schoolId);
        }
        
        // Extract phone numbers
        $phones = array_column($recipients, 'phone');
        
        // Send bulk SMS
        $result = $sms->sendBulkSMS($phones, $message, ['sender_id' => $senderId]);
        
        // Log each SMS according to result
        if (SMS_LOGGING) {
            foreach ($result['results'] as $index => $item) {
                $recipient = $recipients[$index];
                $this->logSMS([
                    'school_id' => $schoolId,
                    'recipient_phone' => $item['phone'],
                    'recipient_name' => $recipient['name'] ?? null,
                    'recipient_type' => $recipientType,
                    'message' => $message,
                    'message_type' => $messageType,
                    'provider' => $provider,
                    'sender_id' => $senderId,
                    'status' => $item['result']['success'] ? 'sent' : 'failed',
                    'api_response' => json_encode($item['result']['response'] ?? []),
                    'message_id' => $item['result']['message_id'] ?? null,
                    'cost' => $item['result']['cost'] ?? null
                ]);
            }
        }
        
        return $result;
    }
    
    /**
     * Get template message with data replacement
     * @param string $template - Template key
     * @param array $data - Data for replacement
     * @return string - Formatted message
     */
    private function getTemplateMessage($template, $data) {
        $template = SMS_TEMPLATES[$template] ?? SMS_TEMPLATES['general'];
        
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        
        return $template;
    }
    
    /**
     * Log SMS to database
     * @param array $data - SMS data
     * @return bool - Success status
     */
    private function logSMS($data) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO sms_logs 
                (school_id, recipient_phone, recipient_name, recipient_type, message, message_type, provider, sender_id, status, api_response, message_id, cost, sent_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            return $stmt->execute([
                $data['school_id'],
                $data['recipient_phone'],
                $data['recipient_name'],
                $data['recipient_type'],
                $data['message'],
                $data['message_type'],
                $data['provider'],
                $data['sender_id'],
                $data['status'],
                $data['api_response'],
                $data['message_id'],
                $data['cost']
            ]);
        } catch (PDOException $e) {
            error_log("Failed to log SMS: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check SMS balance for a school
     * @param int $schoolId - School ID
     * @param string $provider - Provider name
     * @return array - Balance information
     */
    public function checkBalance($schoolId, $provider = null) {
        $provider = $provider ?? $this->defaultProvider;
        
        // Initialize provider
        if ($provider === 'mobitech') {
            $sms = new MobitechSMS();
        } else {
            $sms = new TextSMS();
        }
        
        // Check balance
        $result = $sms->checkBalance();
        
        // Update in database
        if ($result['success']) {
            try {
                $stmt = $this->pdo->prepare("INSERT INTO sms_balance (school_id, provider, balance, last_checked)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE balance = ?, last_checked = NOW()");
                
                $stmt->execute([$schoolId, $provider, $result['balance'], $result['balance']]);
            } catch (PDOException $e) {
                error_log("Failed to update SMS balance: " . $e->getMessage());
            }
        }
        
        return $result;
    }
    
    /**
     * Get SMS logs for a school
     * @param int $schoolId - School ID
     * @param array $filters - Optional filters
     * @return array - SMS logs
     */
    public function getSMSLogs($schoolId, $filters = []) {
        try {
            $query = "SELECT * FROM sms_logs WHERE school_id = ?";
            $params = [$schoolId];
            
            if (!empty($filters['status'])) {
                $query .= " AND status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['provider'])) {
                $query .= " AND provider = ?";
                $params[] = $filters['provider'];
            }
            
            if (!empty($filters['date_from'])) {
                $query .= " AND created_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $query .= " AND created_at <= ?";
                $params[] = $filters['date_to'];
            }
            
            $query .= " ORDER BY created_at DESC";
            
            if (!empty($filters['limit'])) {
                $query .= " LIMIT ?";
                $params[] = $filters['limit'];
            }
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get SMS statistics for a school
     * @param int $schoolId - School ID
     * @return array - Statistics
     */
    public function getSMSStats($schoolId) {
        try {
            $stmt = $this->pdo->prepare("SELECT 
                COUNT(*) as total_sent,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as successfully_sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN provider = 'mobitech' THEN 1 ELSE 0 END) as mobitech_count,
                SUM(CASE WHEN provider = 'textsms' THEN 1 ELSE 0 END) as textsms_count,
                SUM(cost) as total_cost
                FROM sms_logs 
                WHERE school_id = ?");
            
            $stmt->execute([$schoolId]);
            return [
                'success' => true,
                'data' => $stmt->fetch()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>
