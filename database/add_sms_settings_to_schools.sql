-- Add SMS settings columns to schools table
ALTER TABLE `schools` 
ADD COLUMN `sms_provider` enum('mobitech','textsms') DEFAULT 'mobitech' AFTER `max_subjects`,
ADD COLUMN `mobitech_api_key` varchar(255) DEFAULT NULL AFTER `sms_provider`,
ADD COLUMN `textsms_api_key` varchar(255) DEFAULT NULL AFTER `mobitech_api_key`,
ADD COLUMN `sms_sender_id` varchar(50) DEFAULT 'KenyaEduHub' AFTER `textsms_api_key`,
ADD COLUMN `sms_enabled` tinyint(1) DEFAULT 0 AFTER `sms_sender_id`;
