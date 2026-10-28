-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2026 at 06:58 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `users_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_mpesa_settings`
--

CREATE TABLE `admin_mpesa_settings` (
  `id` int(11) NOT NULL,
  `environment` varchar(20) DEFAULT 'sandbox',
  `callback_base_url` varchar(255) DEFAULT NULL,
  `consumer_key` varchar(255) DEFAULT NULL,
  `consumer_secret` varchar(255) DEFAULT NULL,
  `passkey` varchar(255) DEFAULT NULL,
  `business_shortcode` varchar(20) DEFAULT NULL,
  `stk_push_url` varchar(255) DEFAULT NULL,
  `auth_url` varchar(255) DEFAULT NULL,
  `b2c_url` varchar(255) DEFAULT NULL,
  `b2c_shortcode` varchar(20) DEFAULT NULL,
  `b2c_initiator_name` varchar(100) DEFAULT NULL,
  `b2c_initiator_password` varchar(255) DEFAULT NULL,
  `b2c_command_id` varchar(50) DEFAULT NULL,
  `transaction_type` varchar(50) DEFAULT NULL,
  `timeout` int(11) DEFAULT 30,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_mpesa_settings`
--

INSERT INTO `admin_mpesa_settings` (`id`, `environment`, `callback_base_url`, `consumer_key`, `consumer_secret`, `passkey`, `business_shortcode`, `stk_push_url`, `auth_url`, `b2c_url`, `b2c_shortcode`, `b2c_initiator_name`, `b2c_initiator_password`, `b2c_command_id`, `transaction_type`, `timeout`, `updated_at`) VALUES
(1, 'sandbox', 'https://9a53-154-159-237-94.ngrok-free.app', 'opWeqfbSeWQg0lxBf1OWiUuFGzvOAxWm1mJQG2G46kpPVOMJ', '6hU1YYlvAX6I9K5z9e9AS3RVH5D993lM5PL78pjcBevO9KeTlIUwFUchwKDR25Yi', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919', '174379', 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest', 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials', 'https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest', '600997', 'testapi', 'Safaricom123!!', 'SalaryPayment', 'CustomerPayBillOnline', 30, '2026-10-28 17:54:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_mpesa_settings`
--
ALTER TABLE `admin_mpesa_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_mpesa_settings`
--
ALTER TABLE `admin_mpesa_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
