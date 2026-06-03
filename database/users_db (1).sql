-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 29, 2026 at 03:41 PM
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
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(1, 'otienobrian029@gmail.com', 'afab3b479fc0e4ca6acc6951c82ddc35c8604ccdcb87f2f2ea797d51db9b04814a7dab2288752f31276ec396b6855a7eb022', '2025-12-18 20:05:32', '2025-12-18 18:05:32');

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `downloads` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_hash` varchar(32) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `title`, `level`, `subject`, `type`, `description`, `filename`, `downloads`, `created_at`, `file_hash`, `user_id`) VALUES
(11, 'test', 'Primary', 'mathematics', 'DOC', 'UIGDUYYFYIFUYHIUFUHFUF', '69458e223162c_MAINTANANCE APPLICATION.docx', 6, '2025-12-20 08:02:33', NULL, 1),
(14, 'nknknkmnkm', 'Secondary', 'mathematics', 'pdf', 'njmbmjnmj nn', '69458e223162c_MAINTANANCE APPLICATION.docx', 15, '2025-12-20 17:06:44', 'e99226a9b704ebeda5ddbeb7d5bef578', 3),
(20, 'test', 'Secondary', 'Electrical principles', 'PDF', 'hbn jbnkm.,;\'m,klm', '69b029291de4f_LOOPTORRENT.docx', 2, '2026-03-10 14:22:33', '1be5e82cbf76368955f5dec380630c44', 1),
(21, 'jhvnm', 'Primary', 'Electrical principles', 'PDF', 'jbkm m, m,', 'api/uploads/69b02ae8cc597_KENAS CONSTRUCTION AND RENOVATION LTD.docx', 1, '2026-03-10 14:30:00', 'f8b1944b7de9189b0d4081c0d7cfee46', 3),
(22, 'poj0jiojpoi', 'Primary', 'Electrical principles', 'PDF', ' , ,  j n  m, ,knknklnklnkl', 'api/uploads/69b02cd108a90_https.docx', 3, '2026-03-10 14:38:09', '140803be555067a953debe486ceae1b1', 1),
(23, 'test 2', 'Primary', 'Electrical principles', 'DOC', 'nm nm, m,. ,.nkl ,.', 'api/uploads/69b02da23d284_PROJECT.docx', 6, '2026-03-10 14:41:38', 'a0499cd64ba631411e8122a7d859ff20', 3),
(24, 'test 3', 'secondary', 'Electrical principles', 'pdf', 'klnjkrvjjkb , jk', 'api/uploads/69b02e8d4925f_Brian Onyango 2.docx', 9, '2026-03-10 14:45:33', 'af5ab37ef45a68afc078fc26c9d86320', 1),
(25, 'DOMESTIC WATER SUPLY', 'College', 'Solar installation', 'PDF', 'this is domestic water  suply notes pdf', 'api/uploads/69b17e3fcad9e_DOMESTIC WATER SYPPLLY  I     LEARNING OUTLINE.pdf', 23, '2026-03-11 14:37:51', 'd6bb6412e21d0f055cb63343119c6de7', 3),
(26, 'TEST 4', 'Primary', 'Electrical principles', 'PDF', ';HIOE;CKLCN,/C', 'api/uploads/69b17fa0b3a29_p5.pdf', 10, '2026-03-11 14:43:44', '5cda01173befa3a6e23ea23e7d52a4ac', 1),
(27, 'TEST 5', 'College', 'Electrical principles', 'pdf', 'IHJKNJCNWLKNLNC', 'api/uploads/69b181d343036_LEARNING-GUIDE-FOR-BASIC-COMPETENCIES-LEVEL-6 (1).pdf', 29, '2026-03-11 14:53:07', 'be3f5070e42052bfa63b3b4a9ea171da', 3),
(28, 'TEST6', 'College', 'Electrical principles', 'PDF', 'KGV,JN NMNKLIYHGFUCJHNM/UIG', 'api/uploads/69b572d05757b_downloaded_1760873391530.pdf', 3, '2026-03-14 14:38:08', 'd41d8cd98f00b204e9800998ecf8427e', 1),
(29, 'TEST 7', 'Primary', 'Electrical principles', 'PDF', 'YCVKJKCNJKHUIVKHFNKLNVWKLV', 'api/uploads/69b5ac121c0a2_downloaded_1765453715140.pdf', 4, '2026-03-14 18:42:26', '61725cf198f2491fdb1abcc7fc90d58a', 3),
(31, 'EUIFEJIFBNEJKV', 'Primary', 'Electrical principles', 'PDF', 'JINJKL JBJIKNMLN', 'api/uploads/69ba510e7d103_EXTRACTED.pdf', 1, '2026-03-18 07:15:26', '6d34e8b2f851bfaf3a17126862463951', 1),
(32, 'JHVJHJKN', 'Primary', 'Electrical principles', 'PDF', 'VJK/BLB/KLJBNKLN', 'api/uploads/69bd5f25971cd_test 3.pdf', 2, '2026-03-20 14:52:21', '426ea864277f44c2135e05c22229a9eb', 26),
(33, 'UIGJK', 'Primary', 'Solar installation', 'PDF', 'JIJIKBLJKNKLNKL', 'api/uploads/69bd5f692154a_defensive.docx', 1, '2026-03-20 14:53:29', '3ef1a27964e7a5bbdf30e88ccfc57d92', 26),
(34, 'UKFJHBVJK', 'Primary', 'Electrical principles', 'PDF', 'HIGJIKKLB', 'api/uploads/69be2f91e7885_contributions.xlsx', 3, '2026-03-21 05:41:37', '95faba5ed3f68c3a0d0e54382e426087', 1),
(35, 'KGUJK', 'Primary', 'Electrical principles', 'PDF', 'YUGIGJ', 'api/uploads/69be31232fb67_list.xlsx', 4, '2026-03-21 05:48:19', '2f1d0347327f97c8df573798c2ec7e65', 1),
(36, 'jkkjbjk', 'Primary', 'jkjikbjkb', 'PDF', 'jklnjklnkl', 'api/uploads/69be45a917ae6_YYY.pdf', 1, '2026-03-21 07:15:53', '7c4cd2ab1260cd20c732668b71db6db0', 1),
(37, 'KLNJKLCNKLCVK', 'Primary', 'Electrical principles', 'PDF', 'NDJKCNMKCNDMLCNMLCN', 'api/uploads/69be4c2e497b7_sample questioms.pdf', 0, '2026-03-21 07:43:43', 'ebcddf22038eb5dc7bed70c26dd3c51d', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL,
  `verification_code` varchar(6) DEFAULT NULL,
  `code_expires_at` datetime DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `reset_code` varchar(6) DEFAULT NULL,
  `reset_expires_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `verification_code`, `code_expires_at`, `is_verified`, `reset_code`, `reset_expires_at`) VALUES
(1, 'Brian Onyango', 'otienobrian029@gmail.com', '$2y$10$mbbgZh98BWWdD0JAGo98a.cRm7s/szNj2kBid3ceJc8Mq2Ms6a.em', 'admin', NULL, '2025-07-12 13:10:16', 1, '754241', '2026-03-20 22:56:28'),
(26, 'Brian Onyango', 'otisbrian46@gmail.com', '$2y$10$vfafGTj8VaRV91mdP67ACulWs8bPuke.988xpmMLeSfVN0b879tky', 'user', NULL, NULL, 1, NULL, NULL),
(3, 'omar', 'omarwaraka10@gmail.com', '$2y$10$gbcQq8JsJc.Fw1x3HyPn4einwXC.NV063kKBKK4Ttsq93tK5G1EWO', 'user', NULL, NULL, 0, NULL, NULL),
(5, 'stanley juma', 'stareen258@gmail.com', '$2y$10$8/7D1lCSHZF463hPmiouo.uN5q40NVg.4mb6sCnT343akQvaHeT7m', 'user', NULL, NULL, 0, NULL, NULL),
(6, 'William Steve Odhiambo', 'williamsteve10699@gmail.com', '$2y$10$9ILMPtccH2Om9d7sVFacGedBVGTVUg.wKL/xChXBP9GgmQmH58vH6', 'user', NULL, NULL, 0, NULL, NULL),
(7, 'Anjeline Auma', 'anjelineauma@gmail.com', '$2y$10$sBCBTsDBFIkdFrLjGw3rNeOTegcKI4KMzSWYbHAUwHplS1zJLRN7e', 'user', NULL, NULL, 0, NULL, NULL),
(10, 'Jiven ochieng', 'Onsongojiven095@gmail.com', '$2y$10$ibVeVvzzkBfWcLsd2Pwvaef7kXBXEPSXZyuUf3ZRKRzvFXi4M4DyG', 'user', NULL, NULL, 0, NULL, NULL),
(11, 'Brian Philip Ombiro ', 'ombirophilibra@gmail.com', '$2y$10$hWpvE297aEETC/YY82jkPexYDyNdLTGXRopN2Jl/4n6ex4/vtOPEO', 'user', NULL, NULL, 0, NULL, NULL),
(15, 'omundo peter', 'omundopeter@6gmail.com', '$2y$10$1VAAWJTciZrryznlZXQKnuLE5l931VYSfeWkzhAOT4BlSjAk55kIy', 'admin', NULL, NULL, 0, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_file_hash` (`file_hash`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
