-- Create SMS logs table for tracking sent messages
CREATE TABLE IF NOT EXISTS `sms_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `school_id` int(11) NOT NULL,
    `recipient_phone` varchar(20) NOT NULL,
    `recipient_name` varchar(255) DEFAULT NULL,
    `recipient_type` enum('parent','student','teacher','admin') DEFAULT 'parent',
    `message` text NOT NULL,
    `message_type` varchar(50) DEFAULT 'general',
    `provider` enum('mobitech','textsms') DEFAULT 'mobitech',
    `sender_id` varchar(50) DEFAULT 'KenyaEduHub',
    `status` enum('pending','sent','delivered','failed') DEFAULT 'pending',
    `api_response` text DEFAULT NULL,
    `message_id` varchar(100) DEFAULT NULL,
    `cost` decimal(10,2) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `sent_at` timestamp NULL DEFAULT NULL,
    `delivered_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_school_id` (`school_id`),
    KEY `idx_recipient_phone` (`recipient_phone`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create SMS balance tracking table
CREATE TABLE IF NOT EXISTS `sms_balance` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `school_id` int(11) NOT NULL,
    `provider` enum('mobitech','textsms') NOT NULL,
    `balance` int(11) DEFAULT 0,
    `last_checked` timestamp NULL DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_school_provider` (`school_id`,`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
