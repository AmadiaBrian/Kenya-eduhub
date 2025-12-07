
CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `verification_code`, `code_expires_at`, `is_verified`, `reset_code`, `reset_expires_at`) VALUES
(16, 'Brian Onyango', 'otienobrian029@gmail.com', '$2y$10$oaK17F1CLifr0HkjrMK6b.rHxK5WvOoce4C7zo2KotCClGb21fT2i', 'admin', NULL, '2025-07-12 13:10:16', 1, NULL, NULL),
(17, 'Brian Onyango', 'otisbrian46@gmail.com', '$2y$10$GgGGPUg18EtSRDRpVallkO5VQFvI3yMyXQLV/1qcqy4p9t8E8YbZC', 'user', NULL, '2025-07-12 14:27:30', 1, NULL, NULL),
(3, 'omar', 'omarwaraka10@gmail.com', '$2y$10$gbcQq8JsJc.Fw1x3HyPn4einwXC.NV063kKBKK4Ttsq93tK5G1EWO', 'user', NULL, NULL, 0, NULL, NULL),
(4, 'Mike alude', 'mikealude@gmail.com', '$2y$10$fEvsZkFWQkucfZoeEgk1MO28n5NFeVyFxeAXETOrNT1n9kBwcJzjK', 'user', NULL, NULL, 0, NULL, NULL),
(5, 'stanley juma', 'stareen258@gmail.com', '$2y$10$8/7D1lCSHZF463hPmiouo.uN5q40NVg.4mb6sCnT343akQvaHeT7m', 'user', NULL, NULL, 0, NULL, NULL),
(6, 'William Steve Odhiambo', 'williamsteve10699@gmail.com', '$2y$10$9ILMPtccH2Om9d7sVFacGedBVGTVUg.wKL/xChXBP9GgmQmH58vH6', 'user', NULL, NULL, 0, NULL, NULL),
(7, 'Anjeline Auma', 'anjelineauma@gmail.com', '$2y$10$sBCBTsDBFIkdFrLjGw3rNeOTegcKI4KMzSWYbHAUwHplS1zJLRN7e', 'user', NULL, NULL, 0, NULL, NULL),
(8, 'Merab Awino', 'merabawino@gmail.com', '$2y$10$HO9M9LAYxaAzUtmX5.IVL.ovYAKGSgtVGtHtwbZEm/NSlW6BW9XUO', 'user', NULL, NULL, 0, NULL, NULL),
(9, 'Vincent omondi', 'vincentomondi@gmail.com', '$2y$10$IOBoDgbgHG5lLs/aA2ltEuRksK0m5qKotdfqbQ0j2HoyGtmxBD9/C', 'user', NULL, NULL, 0, NULL, NULL),
(10, 'Jiven ochieng', 'Onsongojiven095@gmail.com', '$2y$10$ibVeVvzzkBfWcLsd2Pwvaef7kXBXEPSXZyuUf3ZRKRzvFXi4M4DyG', 'user', NULL, NULL, 0, NULL, NULL),
(11, 'Brian Philip Ombiro ', 'ombirophilibra@gmail.com', '$2y$10$hWpvE297aEETC/YY82jkPexYDyNdLTGXRopN2Jl/4n6ex4/vtOPEO', 'user', NULL, NULL, 0, NULL, NULL),
(12, 'Vincent ', 'vincentomollo22@gmail.com', '$2y$10$NAwqQn/PAzX95slgAtKNEOzc4abyW9iAvUdO7wLcgBGVyrZybI1R.', 'user', NULL, NULL, 0, NULL, NULL),
(13, 'Elijah ', 'eliochieng36@gmail.com', '$2y$10$XgwQj.ycvhUldLo/ox3fO.S4.T7EVvCHcRuYIx0ipiGIXO7sxwacq', 'user', NULL, NULL, 0, NULL, NULL),
(14, 'Shadrack  ochieng ', 'ochiengshady40@gmail.com', '$2y$10$oh/2Ei3BOfocWfU88ZdQfeNaOus9PAVHEyESaPx38jPmM7zfKvuqG', 'user', NULL, NULL, 0, NULL, NULL),
(15, 'omundo peter', 'omundopeter@6gmail.com', '$2y$10$EGHN6GvFp9wLVTmm1senje1/uGRDL4GMIwAY7IKh1cBKqQYFDnrga', 'admin', NULL, NULL, 0, NULL, NULL);
