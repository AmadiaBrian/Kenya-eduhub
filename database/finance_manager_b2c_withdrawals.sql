-- Finance Manager withdrawal tables
-- Import this into the users_db database before using finance-manager withdrawals.

CREATE TABLE IF NOT EXISTS `school_withdrawals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `finance_manager_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `destination_type` varchar(30) NOT NULL DEFAULT 'phone',
  `destination_name` varchar(150) DEFAULT NULL,
  `destination_account` varchar(150) NOT NULL,
  `destination_extra` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','failed','success') NOT NULL DEFAULT 'pending',
  `reference_number` varchar(60) NOT NULL,
  `originator_conversation_id` varchar(100) DEFAULT NULL,
  `conversation_id` varchar(100) DEFAULT NULL,
  `mpesa_receipt_number` varchar(100) DEFAULT NULL,
  `result_code` varchar(20) DEFAULT NULL,
  `result_desc` text DEFAULT NULL,
  `success_at` datetime DEFAULT NULL,
  `balance_deducted_at` datetime DEFAULT NULL,
  `callback_payload` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_number` (`reference_number`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_finance_manager_id` (`finance_manager_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_originator_conversation_id` (`originator_conversation_id`),
  KEY `idx_conversation_id` (`conversation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stores Safaricom B2C callback responses for phone withdrawals.
CREATE TABLE IF NOT EXISTS `school_b2c_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `withdrawal_id` int(11) DEFAULT NULL,
  `callback_type` enum('result','timeout') NOT NULL DEFAULT 'result',
  `result_code` varchar(20) DEFAULT NULL,
  `result_desc` text DEFAULT NULL,
  `originator_conversation_id` varchar(100) DEFAULT NULL,
  `conversation_id` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `transaction_amount` decimal(10,2) DEFAULT NULL,
  `receiver_party` varchar(255) DEFAULT NULL,
  `transaction_completed_at` varchar(80) DEFAULT NULL,
  `raw_response` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_withdrawal_id` (`withdrawal_id`),
  KEY `idx_callback_type` (`callback_type`),
  KEY `idx_result_code` (`result_code`),
  KEY `idx_originator_conversation_id` (`originator_conversation_id`),
  KEY `idx_conversation_id` (`conversation_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- If you already created school_withdrawals from an older version, run these
-- compatibility statements manually as needed.
-- ALTER TABLE `school_withdrawals` MODIFY `status` enum('pending','failed','success') NOT NULL DEFAULT 'pending';
-- ALTER TABLE `school_withdrawals` ADD COLUMN `success_at` datetime DEFAULT NULL;
