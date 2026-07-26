-- Add separate sender ID columns for each SMS provider
ALTER TABLE `schools` 
ADD COLUMN `mobitech_sender_id` varchar(50) DEFAULT NULL AFTER `mobitech_api_key`,
ADD COLUMN `textsms_sender_id` varchar(50) DEFAULT NULL AFTER `textsms_partner_id`;
