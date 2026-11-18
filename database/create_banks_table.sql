-- Banks Table for B2B Disbursements
-- Used by schools to withdraw funds directly to their official bank accounts via M-Pesa B2B Paybill

CREATE TABLE IF NOT EXISTS `banks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bank_name` VARCHAR(100) NOT NULL,
    `paybill_number` VARCHAR(20) NOT NULL,
    `account_hint` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pre-populate default Kenyan commercial banks with their official M-Pesa Paybill numbers
INSERT INTO `banks` (`bank_name`, `paybill_number`, `account_hint`, `status`) VALUES
('Equity Bank', '247247', '13-digit Account Number (e.g. 0123456789012)', 'active'),
('KCB Bank', '522522', '10-digit Account Number', 'active'),
('Co-operative Bank', '400200', '14-digit Account Number', 'active'),
('ABSA Bank Kenya', '303030', '10-digit Account Number', 'active'),
('NCBA Bank', '888888', 'Account Number', 'active'),
('Stanbic Bank', '522533', '10-digit Account Number', 'active'),
('Diamond Trust Bank (DTB)', '516600', 'Account Number', 'active'),
('Family Bank', '222111', '12-digit Account Number', 'active'),
('I&M Bank', '542542', 'Account Number', 'active'),
('Standard Chartered Bank', '329329', 'Account Number', 'active'),
('National Bank of Kenya (NBK)', '625625', 'Account Number', 'active'),
('Sidian Bank', '111999', 'Account Number', 'active'),
('Postbank', '200999', 'Account Number', 'active')
ON DUPLICATE KEY UPDATE `bank_name` = VALUES(`bank_name`);
