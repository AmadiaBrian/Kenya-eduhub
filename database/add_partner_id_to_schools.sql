-- Add partner_id column to schools table for Text SMS
ALTER TABLE `schools` 
ADD COLUMN `textsms_partner_id` varchar(50) DEFAULT NULL AFTER `textsms_api_key`;
