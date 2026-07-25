-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 25, 2026 at 02:57 AM
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
-- Table structure for table `academic_performance`
--

CREATE TABLE `academic_performance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `term` varchar(50) NOT NULL,
  `year` int(4) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `exam_type_id` int(11) DEFAULT NULL,
  `marks` decimal(5,2) NOT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_performance`
--

INSERT INTO `academic_performance` (`id`, `student_id`, `term`, `year`, `subject`, `exam_type_id`, `marks`, `grade`, `remarks`, `created_at`) VALUES
(1, 4, 'Term 1', 2026, 'PHYSICS', NULL, 60.00, 'A', 'Exelent', '2026-07-04 20:06:10'),
(5, 4, 'Term 1', 2026, 'CHEMISTRY', NULL, 65.00, 'A', 'Exelent', '2026-07-19 15:10:34'),
(6, 4, 'Term 2', 2026, 'PHYSICS', 1, 50.00, 'B+', 'Exelent', '2026-07-24 19:54:01'),
(7, 4, 'Term 2', 2026, 'MATHEMATICS', 1, 50.00, 'B+', 'Exelent', '2026-07-24 22:08:37'),
(8, 4, 'Term 2', 2026, 'KISWAHILI', 1, 60.00, 'A', 'Exelent', '2026-07-24 22:10:07'),
(9, 4, 'Term 2', 2026, 'HISTORY AND GOVERNMENT', 1, 80.00, 'A', 'Exelent', '2026-07-24 22:11:01'),
(10, 4, 'Term 2', 2026, 'GEOGRAPHY', 1, 60.00, 'A', 'Exelent', '2026-07-24 22:11:47'),
(11, 4, 'Term 2', 2026, 'ENGLISH', 1, 55.00, 'A-', 'Exelent', '2026-07-24 22:12:33'),
(12, 4, 'Term 2', 2026, 'CRE', 1, 70.00, 'A', 'Exelent', '2026-07-24 22:13:26'),
(13, 4, 'Term 2', 2026, 'CHEMISTRY', 1, 70.00, 'A', 'Exelent', '2026-07-24 22:14:07'),
(14, 4, 'Term 2', 2026, 'BUSINESSES STUDIES', 1, 58.00, 'A-', 'Exelent', '2026-07-24 22:15:02'),
(15, 4, 'Term 2', 2026, 'BIOLOGY', 1, 77.00, 'A', 'Exelent', '2026-07-24 22:15:48'),
(16, 4, 'Term 2', 2026, 'AGRICULTURE', 1, 62.00, 'A', 'Exelent', '2026-07-24 22:17:06');

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assignment_type` enum('syllabus','sentiment','notes','holiday') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `teacher_id`, `school_id`, `class_id`, `subject_id`, `title`, `description`, `assignment_type`, `file_path`, `file_name`, `due_date`, `created_at`, `updated_at`) VALUES
(4, 1, 1, 1, NULL, 'chemisry asinment', 'this is chemistry asinment', 'holiday', '6a5d7c0521615_1784511493.pdf', 'Automated_Garden_Watering_System..pdf', '2026-08-01', '2026-07-20 01:38:13', '2026-07-20 01:38:13'),
(6, 1, 1, 1, NULL, 'physiscs asinment', 'this si test for physics asinment', 'notes', '6a5d7d0d7c2e3_1784511757.docx', 'LINKS.docx', '2026-08-08', '2026-07-20 01:42:37', '2026-07-20 01:42:37');

-- --------------------------------------------------------

--
-- Table structure for table `assignment_comments`
--

CREATE TABLE `assignment_comments` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `author_type` enum('teacher','parent','student') DEFAULT 'teacher',
  `author_name` varchar(255) DEFAULT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignment_comments`
--

INSERT INTO `assignment_comments` (`id`, `assignment_id`, `author_id`, `author_type`, `author_name`, `comment`, `created_at`) VALUES
(1, 6, 1, 'teacher', 'ROBINSON OMOLLO', 'TEST', '2026-07-21 23:38:12');

-- --------------------------------------------------------

--
-- Table structure for table `assignment_downloads`
--

CREATE TABLE `assignment_downloads` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `user_type` enum('teacher','parent','student') NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `download_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignment_downloads`
--

INSERT INTO `assignment_downloads` (`id`, `assignment_id`, `user_type`, `user_id`, `user_name`, `download_date`) VALUES
(7, 6, 'parent', 2, 'SAMUEL OKECH', '2026-07-20 01:58:58'),
(8, 6, 'teacher', 1, 'ROBINSON OMOLLO', '2026-07-21 23:28:30'),
(9, 4, 'parent', 2, 'SAMUEL OKECH', '2026-07-21 23:55:23');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `date`, `status`, `remarks`, `created_at`) VALUES
(1, 4, '2026-07-04', 'present', '', '2026-07-04 19:50:59'),
(2, 4, '2026-07-05', 'excused', '', '2026-07-05 13:11:25'),
(3, 4, '2026-07-06', 'present', '', '2026-07-07 19:23:33'),
(4, 4, '2026-07-22', 'present', '', '2026-07-22 00:02:32');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_year` int(4) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `total_copies` int(11) NOT NULL DEFAULT 0,
  `available_copies` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `book_price` decimal(10,2) DEFAULT 0.00,
  `shelf_location` varchar(50) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `condition` enum('new','good','fair','poor','damaged') DEFAULT 'new',
  `status` enum('available','unavailable') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `school_id`, `isbn`, `title`, `author`, `publisher`, `publication_year`, `category`, `total_copies`, `available_copies`, `description`, `cover_image`, `book_price`, `shelf_location`, `section`, `condition`, `status`, `created_at`, `updated_at`) VALUES
(4, 1, '9780123456789', 'Test Book for Library System', 'Test Author', 'Test Publisher', 2024, 'Fiction', 4, 7, 'This is a test book for testing the library management system.', 'uploads/book_covers/book_6a62a5b4429868.91443298.png', 500.00, '', '', 'new', 'available', '2026-07-22 16:02:09', '2026-07-24 18:27:47'),
(5, 1, 'OVERDUE-TEST-001', 'Overdue Test Book', 'Test Author', 'Test Publisher', 2024, 'Fiction', 3, 3, 'Test book specifically for overdue testing', 'uploads/book_covers/book_6a63ad7579c462.17990681.png', 200.00, '', '', 'new', 'available', '2026-07-24 00:03:15', '2026-07-24 18:22:45');

-- --------------------------------------------------------

--
-- Table structure for table `book_borrowings`
--

CREATE TABLE `book_borrowings` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrower_type` enum('student','teacher') NOT NULL,
  `borrower_id` int(11) NOT NULL,
  `borrow_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed',
  `notes` text DEFAULT NULL,
  `book_condition` varchar(20) DEFAULT 'good',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book_borrowings`
--

INSERT INTO `book_borrowings` (`id`, `book_id`, `borrower_type`, `borrower_id`, `borrow_date`, `due_date`, `return_date`, `status`, `notes`, `book_condition`, `created_at`, `updated_at`) VALUES
(3, 4, 'student', 4, '2026-07-12', '2026-07-17', '2026-07-24', 'overdue', 'TEST', 'good', '2026-07-22 16:03:14', '2026-07-23 23:48:40'),
(4, 4, 'student', 4, '2026-07-24', '2026-07-30', '2026-07-24', 'returned', 'TEST', 'good', '2026-07-23 23:46:00', '2026-07-23 23:48:25'),
(5, 4, 'student', 4, '2026-07-04', '2026-07-14', '2026-07-24', '', 'test', 'damaged', '2026-07-24 00:01:47', '2026-07-24 14:05:55'),
(6, 5, 'student', 4, '2026-06-24', '2026-07-09', '2026-07-24', 'overdue', '', 'good', '2026-07-24 00:03:15', '2026-07-24 00:09:53'),
(7, 4, 'student', 4, '2026-07-04', '2026-07-14', '2026-07-24', '', 'TEST', 'lost', '2026-07-24 18:24:31', '2026-07-24 18:25:38'),
(8, 4, 'student', 4, '2026-07-04', '2026-07-14', '2026-07-24', '', 'TEST', 'damaged', '2026-07-24 18:27:08', '2026-07-24 18:27:47');

-- --------------------------------------------------------

--
-- Table structure for table `book_categories`
--

CREATE TABLE `book_categories` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book_categories`
--

INSERT INTO `book_categories` (`id`, `school_id`, `category_name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'TEST', 'FOR SCHOOL', 'active', '2026-07-22 14:18:42', '2026-07-22 14:18:42'),
(2, 1, 'Fiction', 'Test category', 'active', '2026-07-22 16:01:41', '2026-07-22 16:01:41');

-- --------------------------------------------------------

--
-- Table structure for table `book_history`
--

CREATE TABLE `book_history` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `action` enum('added','edited','deleted','borrowed','returned','reserved','lost','damaged') NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('librarian','student','teacher') NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book_history`
--

INSERT INTO `book_history` (`id`, `book_id`, `school_id`, `action`, `user_id`, `user_type`, `details`, `created_at`) VALUES
(6, 4, 1, '', 0, '', 'Fine issued: 50 for overdue borrowing ID: 3', '2026-07-22 16:03:14'),
(8, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 16:13:33'),
(9, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 16:13:42'),
(10, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 16:18:45'),
(11, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 16:18:55'),
(12, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 16:19:40'),
(13, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 16:20:04'),
(14, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 16:22:54'),
(15, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 16:23:04'),
(16, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 16:29:02'),
(17, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 16:29:12'),
(18, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 2 for fine ID: 6', '2026-07-22 16:29:47'),
(19, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 16:30:12'),
(20, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 16:31:24'),
(21, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 16:31:32'),
(22, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 16:34:02'),
(23, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 16:34:29'),
(24, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 16:38:10'),
(25, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 16:38:18'),
(26, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 16:40:04'),
(27, 4, 1, '', 0, '', 'MPESA payment successful: 1, Receipt: UGMN80E49M for fine ID: 6', '2026-07-22 16:40:16'),
(28, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 16:41:18'),
(29, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 16:41:32'),
(30, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 16:41:48'),
(31, 4, 1, '', 0, '', 'MPESA payment successful: 1, Receipt: UGMN80DZXL for fine ID: 6', '2026-07-22 16:42:04'),
(32, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 16:44:45'),
(33, 4, 1, '', 0, '', 'MPESA payment failed: The initiator information is invalid. for fine ID: 6', '2026-07-22 16:44:56'),
(34, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 16:51:13'),
(35, 4, 1, '', 0, '', 'MPESA payment successful: 1, Receipt: UGMN80E4ND for fine ID: 6', '2026-07-22 16:51:25'),
(36, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 47 for fine ID: 6', '2026-07-22 16:55:40'),
(37, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 16:55:49'),
(38, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 16:59:44'),
(39, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 16:59:56'),
(40, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 47 for fine ID: 6', '2026-07-22 17:01:05'),
(41, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 18:53:37'),
(42, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 18:53:45'),
(43, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 47 for fine ID: 6', '2026-07-22 19:03:36'),
(44, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 19:03:49'),
(45, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 47 for fine ID: 6', '2026-07-22 19:05:46'),
(46, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 19:05:55'),
(47, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 47 for fine ID: 6', '2026-07-22 19:13:35'),
(48, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 19:13:43'),
(49, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 19:14:19'),
(50, 4, 1, '', 0, '', 'MPESA payment successful: 1, Receipt: UGMN80ELXU for fine ID: 6', '2026-07-22 19:14:35'),
(51, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 46 for fine ID: 6', '2026-07-22 19:16:31'),
(52, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 19:16:39'),
(53, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 46 for fine ID: 6', '2026-07-22 19:20:24'),
(54, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 19:20:45'),
(55, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 46 for fine ID: 6', '2026-07-22 19:22:50'),
(56, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 19:23:02'),
(57, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 46 for fine ID: 6', '2026-07-22 19:23:49'),
(58, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 19:24:02'),
(59, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 46 for fine ID: 6', '2026-07-22 19:25:26'),
(60, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 19:25:37'),
(61, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 46 for fine ID: 6', '2026-07-22 19:26:37'),
(62, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 19:26:51'),
(63, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 46 for fine ID: 6', '2026-07-22 19:29:40'),
(64, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 19:29:49'),
(65, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 19:30:13'),
(66, 4, 1, '', 0, '', 'MPESA payment successful: 1, Receipt: UGMN80ES0M for fine ID: 6', '2026-07-22 19:30:24'),
(67, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 19:34:09'),
(68, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 19:34:30'),
(69, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-22 19:34:53'),
(70, 4, 1, '', 0, '', 'MPESA payment successful: 1, Receipt: UGMN80ES2Y for fine ID: 6', '2026-07-22 19:35:07'),
(71, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 44 for fine ID: 6', '2026-07-22 19:37:32'),
(72, 4, 1, '', 0, '', 'MPESA payment failed: No response from user. for fine ID: 6', '2026-07-22 19:38:06'),
(73, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 44 for fine ID: 6', '2026-07-22 19:38:09'),
(74, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 19:38:28'),
(75, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 44 for fine ID: 6', '2026-07-22 20:03:11'),
(76, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 20:03:25'),
(77, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 44 for fine ID: 6', '2026-07-22 21:13:05'),
(78, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-22 21:13:17'),
(79, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-23 22:36:44'),
(80, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 6', '2026-07-23 22:36:53'),
(81, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 6', '2026-07-23 22:40:06'),
(82, 4, 1, '', 0, '', 'MPESA payment successful: 1, Receipt: UGON80IRKO for fine ID: 6', '2026-07-23 22:40:18'),
(83, 4, 1, 'edited', 1, 'librarian', 'Edited book: Test Book for Library System by Test Author', '2026-07-23 23:37:24'),
(84, 4, 1, 'edited', 1, 'librarian', 'Edited book: Test Book for Library System by Test Author', '2026-07-24 12:38:48'),
(85, 4, 1, '', 0, '', 'Fine issued: 500.00 for lost book (10 days overdue): Test Book for Library System', '2026-07-24 13:19:52'),
(86, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 26', '2026-07-24 13:24:31'),
(87, 4, 1, '', 0, '', 'MPESA payment failed: No response from user. for fine ID: 26', '2026-07-24 13:24:59'),
(88, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 500 for fine ID: 26', '2026-07-24 13:25:12'),
(89, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 26', '2026-07-24 13:25:16'),
(90, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 26', '2026-07-24 13:25:36'),
(91, 4, 1, '', 0, '', 'MPESA payment failed: No response from user. for fine ID: 26', '2026-07-24 13:26:04'),
(92, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 26', '2026-07-24 13:26:53'),
(93, 4, 1, '', 0, '', 'MPESA payment failed: No response from user. for fine ID: 26', '2026-07-24 13:27:22'),
(94, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 26', '2026-07-24 13:27:50'),
(95, 4, 1, '', 0, '', 'MPESA payment successful: 1, Receipt: UGON80L1VF for fine ID: 26', '2026-07-24 13:28:04'),
(96, 5, 1, 'edited', 1, 'librarian', 'Edited book: Overdue Test Book by Test Author', '2026-07-24 18:22:28'),
(97, 5, 1, 'edited', 1, 'librarian', 'Edited book: Overdue Test Book by Test Author', '2026-07-24 18:22:45'),
(98, 4, 1, '', 1, 'librarian', 'Fine issued: 500.00 for lost book: ', '2026-07-24 18:25:38'),
(99, 4, 1, '', 1, 'librarian', 'Fine issued: 125 for damaged book: ', '2026-07-24 18:27:47'),
(100, 4, 1, '', 2, '', 'Payment: 1 via mpesa (Parent: 2) (Phone: 0745959757, Ref: MPESA-1784934200-6389)', '2026-07-24 23:03:20'),
(101, 4, 1, '', 2, '', 'MPESA payment initiated: 1 for fine ID: 28', '2026-07-24 23:09:36'),
(102, 4, 1, '', 2, '', 'MPESA payment initiated: 1 for fine ID: 28', '2026-07-24 23:13:33'),
(103, 4, 1, '', 2, '', 'MPESA payment initiated: 1 for fine ID: 28', '2026-07-24 23:13:42'),
(104, 4, 1, '', 2, '', 'MPESA payment initiated: 1 for fine ID: 28', '2026-07-24 23:15:13'),
(105, 4, 1, '', 2, '', 'MPESA payment initiated: 1 for fine ID: 28', '2026-07-24 23:20:19'),
(106, 4, 1, '', 2, '', 'MPESA payment initiated: 1 for fine ID: 28', '2026-07-24 23:21:47'),
(107, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 1 for fine ID: 28', '2026-07-24 23:23:37'),
(108, 4, 1, '', 2, '', 'MPESA payment initiated: 1 for fine ID: 28', '2026-07-24 23:24:12'),
(109, 4, 1, '', 2, '', 'MPESA payment initiated: 1 for fine ID: 28', '2026-07-24 23:26:11'),
(110, 4, 1, '', 2, '', 'MPESA payment initiated: 124 for fine ID: 28', '2026-07-24 23:27:38'),
(111, 4, 1, '', 2, '', 'MPESA payment initiated: 124 for fine ID: 28', '2026-07-24 23:29:00'),
(112, 4, 1, '', 2, '', 'MPESA payment initiated: 124 for fine ID: 28', '2026-07-24 23:30:10'),
(113, 4, 1, '', 2, '', 'MPESA payment initiated: 124 for fine ID: 28', '2026-07-24 23:30:56'),
(114, 4, 1, '', 2, '', 'MPESA payment initiated: 124 for fine ID: 28', '2026-07-24 23:32:53'),
(115, 4, 1, '', 2, '', 'MPESA payment initiated: 124 for fine ID: 28', '2026-07-24 23:36:23'),
(116, 4, 1, '', 2, '', 'MPESA payment initiated: 124 for fine ID: 28', '2026-07-24 23:37:25'),
(117, 4, 1, '', 2, '', 'MPESA payment initiated: 124 for fine ID: 28', '2026-07-24 23:39:48'),
(118, 4, 1, '', 2, '', 'MPESA payment initiated: 124 for fine ID: 28', '2026-07-24 23:40:37'),
(119, 4, 1, '', 2, '', 'MPESA payment initiated: 124 for fine ID: 28', '2026-07-24 23:44:52'),
(120, 4, 1, '', 2, '', 'MPESA payment initiated: 124 for fine ID: 28', '2026-07-24 23:45:53'),
(121, 4, 1, '', 2, '', 'MPESA payment initiated: 124 for fine ID: 28', '2026-07-24 23:46:29'),
(122, 4, 1, '', 2, '', 'MPESA payment initiated: 124 for fine ID: 28', '2026-07-24 23:47:12'),
(123, 4, 1, '', 2, '', 'MPESA payment initiated: 1 for fine ID: 28', '2026-07-24 23:48:25'),
(124, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 28', '2026-07-24 23:48:34'),
(125, 4, 1, '', 2, '', 'MPESA payment initiated: 1 for fine ID: 28', '2026-07-24 23:49:33'),
(126, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 28', '2026-07-24 23:49:41'),
(127, 4, 1, '', 2, '', 'MPESA payment initiated: 1 for fine ID: 28', '2026-07-24 23:50:01'),
(128, 4, 1, '', 0, '', 'MPESA payment failed: The initiator information is invalid. for fine ID: 28', '2026-07-24 23:50:11'),
(129, 4, 1, '', 2, '', 'MPESA payment initiated: 1 for fine ID: 28', '2026-07-24 23:50:27'),
(130, 4, 1, '', 0, '', 'MPESA payment successful: 1, Receipt: UGPN80MX5K for fine ID: 28', '2026-07-24 23:50:40'),
(131, 4, 1, '', 2, '', 'MPESA payment initiated: 123 for fine ID: 28', '2026-07-25 00:04:03'),
(132, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 28', '2026-07-25 00:04:11'),
(133, 4, 1, '', 2, '', 'MPESA payment initiated: 1 for fine ID: 28', '2026-07-25 00:31:05'),
(134, 4, 1, '', 2, '', 'MPESA payment initiated: 123 for fine ID: 28', '2026-07-25 00:32:36'),
(135, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 123 for fine ID: 28', '2026-07-25 00:33:37'),
(136, 4, 1, '', 2, '', 'MPESA payment initiated: 123 for fine ID: 28', '2026-07-25 00:34:14'),
(137, 4, 1, '', 2, '', 'MPESA payment initiated: 1 for fine ID: 28', '2026-07-25 00:35:58'),
(138, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 123 for fine ID: 28', '2026-07-25 00:37:51'),
(139, 4, 1, '', 1, 'librarian', 'MPESA payment initiated: 123 for fine ID: 28', '2026-07-25 00:39:33'),
(140, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 28', '2026-07-25 00:39:40'),
(141, 4, 1, '', 2, '', 'MPESA payment initiated: 123 for fine ID: 28', '2026-07-25 00:40:14'),
(142, 4, 1, '', 0, '', 'MPESA payment failed: Request Cancelled by user. for fine ID: 28', '2026-07-25 00:40:21');

-- --------------------------------------------------------

--
-- Table structure for table `book_reservations`
--

CREATE TABLE `book_reservations` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('student','teacher') NOT NULL,
  `reservation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry_date` timestamp NULL DEFAULT NULL,
  `status` enum('pending','fulfilled','cancelled','expired') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `class_level` varchar(50) NOT NULL,
  `capacity` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `school_id`, `class_name`, `class_level`, `capacity`, `created_at`, `updated_at`) VALUES
(1, 1, 'GRADE 10', 'Secondary', 300, '2026-07-04 15:10:37', '2026-07-04 15:10:37'),
(2, 1, 'GRADE 11', 'Secondary', 80, '2026-07-05 19:01:05', '2026-07-05 19:01:05'),
(3, 1, 'GRADE 12', 'Secondary', 80, '2026-07-05 19:01:38', '2026-07-05 19:01:38');

-- --------------------------------------------------------

--
-- Table structure for table `cleanliness_checks`
--

CREATE TABLE `cleanliness_checks` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `area` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinary_action_types`
--

CREATE TABLE `disciplinary_action_types` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL COMMENT 'Code for the action type (e.g., warning, suspension)',
  `action_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('minor','moderate','severe','critical') DEFAULT 'moderate',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disciplinary_action_types`
--

INSERT INTO `disciplinary_action_types` (`id`, `school_id`, `action_type`, `action_name`, `description`, `severity`, `is_active`, `created_at`) VALUES
(1, 1, 'warning', 'Warning', 'Formal warning for minor misconduct', 'minor', 1, '2026-07-07 12:45:06'),
(2, 1, 'suspension', 'Suspension', 'Temporary removal from school for disciplinary reasons', 'severe', 1, '2026-07-07 12:45:06'),
(3, 1, 'expulsion', 'Expulsion', 'Permanent removal from school', 'critical', 1, '2026-07-07 12:45:06'),
(4, 1, 'probation', 'Probation', 'Student placed on probationary period', 'moderate', 1, '2026-07-07 12:45:06'),
(5, 1, 'transfer', 'Transfer', 'Student transferred to another institution', 'moderate', 1, '2026-07-07 12:45:06'),
(6, 1, 'death', 'Death', 'Student has passed away', 'critical', 1, '2026-07-07 12:45:06'),
(13, 1, 'other', 'Other', 'Other disciplinary actions not categorized', 'moderate', 1, '2026-07-07 13:34:38');

-- --------------------------------------------------------

--
-- Table structure for table `disciplinary_committee`
--

CREATE TABLE `disciplinary_committee` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('admin','teacher','staff') NOT NULL,
  `role` enum('chair','member','secretary','observer') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `appointed_date` date NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinary_records`
--

CREATE TABLE `disciplinary_records` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `action_type` enum('warning','suspension','expulsion','probation','transfer','death','other') NOT NULL,
  `severity` enum('minor','moderate','severe','critical') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `incident_date` date NOT NULL,
  `action_date` date NOT NULL,
  `end_date` date DEFAULT NULL COMMENT 'For suspensions - when the suspension ends',
  `reported_by` varchar(255) NOT NULL COMMENT 'Name of person reporting the incident',
  `handled_by` varchar(255) NOT NULL COMMENT 'Name of person handling the case',
  `status` enum('pending','active','resolved','appealed','closed') DEFAULT 'pending',
  `notes` text DEFAULT NULL COMMENT 'Additional notes or follow-up actions',
  `evidence_files` varchar(500) DEFAULT NULL COMMENT 'Comma-separated list of file paths',
  `parent_notified` tinyint(1) DEFAULT 0,
  `parent_response` text DEFAULT NULL COMMENT 'Parent response to the disciplinary action',
  `appeal_details` text DEFAULT NULL COMMENT 'If student/parent appeals the decision',
  `appeal_status` enum('none','pending','approved','rejected') DEFAULT 'none',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) NOT NULL COMMENT 'User ID who created the record'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disciplinary_records`
--

INSERT INTO `disciplinary_records` (`id`, `school_id`, `student_id`, `action_type`, `severity`, `title`, `description`, `incident_date`, `action_date`, `end_date`, `reported_by`, `handled_by`, `status`, `notes`, `evidence_files`, `parent_notified`, `parent_response`, `appeal_details`, `appeal_status`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 1, 4, 'warning', 'moderate', 'TEST', 'TESTING', '2026-07-02', '2026-07-07', '0000-00-00', 'TEST', 'TEST', 'pending', 'TESTING', '', 0, NULL, NULL, 'none', '2026-07-07 13:52:12', '2026-07-07 13:52:12', 1),
(2, 1, 4, 'suspension', 'minor', 'Test Suspension for PDF Generation', 'This is a test disciplinary record created for testing PDF document generation functionality. The student was suspended for testing purposes.', '2026-07-07', '2026-07-07', '2026-07-14', 'Test Administrator', 'Principal', 'closed', '', NULL, 0, NULL, NULL, 'none', '2026-07-07 13:54:40', '2026-07-07 15:05:57', 1),
(3, 1, 4, 'other', 'minor', 'TEST', 'TEST', '2026-07-08', '2026-07-08', '0000-00-00', 'TEST', 'TEST', 'closed', 'TEST', '', 0, NULL, NULL, 'none', '2026-07-08 19:57:29', '2026-07-19 16:35:24', 1);

-- --------------------------------------------------------

--
-- Table structure for table `duty_assignments`
--

CREATE TABLE `duty_assignments` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `duty_type` varchar(50) DEFAULT 'weekly',
  `week_start` date NOT NULL,
  `week_end` date NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `duty_assignments`
--

INSERT INTO `duty_assignments` (`id`, `school_id`, `teacher_id`, `duty_type`, `week_start`, `week_end`, `assigned_by`, `status`, `created_at`) VALUES
(1, 1, 1, 'weekly', '2026-07-08', '2026-07-14', 1, 'active', '2026-07-08 20:53:28');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `exam_type_id` int(11) NOT NULL,
  `exam_name` varchar(200) NOT NULL,
  `term` varchar(50) NOT NULL,
  `year` int(4) NOT NULL,
  `exam_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `venue` varchar(100) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `status` enum('draft','scheduled','completed','cancelled') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

CREATE TABLE `exam_results` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_types`
--

CREATE TABLE `exam_types` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `exam_type_name` varchar(100) NOT NULL,
  `exam_type_code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_types`
--

INSERT INTO `exam_types` (`id`, `school_id`, `exam_type_name`, `exam_type_code`, `description`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'END TERM', '001', 'TESTING', 1, 1, '2026-07-24 19:10:02', '2026-07-24 19:10:02');

-- --------------------------------------------------------

--
-- Table structure for table `fee_payments`
--

CREATE TABLE `fee_payments` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `term` varchar(50) NOT NULL,
  `year` int(4) NOT NULL,
  `fee_type` varchar(100) DEFAULT 'Tuition',
  `receipt_number` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_payments`
--

INSERT INTO `fee_payments` (`id`, `school_id`, `student_id`, `amount`, `payment_date`, `payment_method`, `status`, `transaction_id`, `term`, `year`, `fee_type`, `receipt_number`, `created_at`) VALUES
(1, 1, 4, 10000.00, '2026-07-04', 'Cash', 'completed', NULL, 'Term 1', 2026, 'Tuition', 'RCP5387B24511', '2026-07-04 16:22:10'),
(4, 1, 4, 10000.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026161947741745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BAB718842E-4', '2026-07-06 13:19:45'),
(5, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026162245832745959757', 'Term 1', 2026, 'RMADIAL', 'FEE-6A4BAC23849C8-4', '2026-07-06 13:22:43'),
(6, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026162721054745959757', 'Term 1', 2026, 'RMADIAL', 'FEE-6A4BAD3685A24-4', '2026-07-06 13:27:18'),
(7, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026162937457745959757', 'Term 1', 2026, 'RMADIAL', 'FEE-6A4BADBF3A697-4', '2026-07-06 13:29:35'),
(9, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026164502622745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BB15C0D457-4', '2026-07-06 13:45:00'),
(10, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026164809092745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BB216BF201-4', '2026-07-06 13:48:06'),
(11, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026165312339745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BB346012F3-4', '2026-07-06 13:53:10'),
(12, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026165804641745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BB46A39825-4', '2026-07-06 13:58:02'),
(13, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026165919353745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BB4B5461EA-4', '2026-07-06 13:59:17'),
(15, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026170344621745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BB5BE7CFA9-4', '2026-07-06 14:03:42'),
(17, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026170821085745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BB6D2F0658-4', '2026-07-06 14:08:18'),
(18, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026171221337745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BB7C30E2F2-4', '2026-07-06 14:12:19'),
(19, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026171254912745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BB7E50A040-4', '2026-07-06 14:12:53'),
(20, 1, 4, 9999.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026171844486745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BB942AF300-4', '2026-07-06 14:18:42'),
(21, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026171902319745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BB95414111-4', '2026-07-06 14:19:00'),
(22, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026171929700745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BB96FA4ED9-4', '2026-07-06 14:19:27'),
(23, 1, 4, 9999.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026172002247745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BB99000731-4', '2026-07-06 14:20:00'),
(24, 1, 4, 9.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026172019159745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BB9A130BC9-4', '2026-07-06 14:20:17'),
(25, 1, 4, 100.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026172105511745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BB9CF73BF1-4', '2026-07-06 14:21:03'),
(26, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'completed', 'ws_CO_06072026172559027745959757', 'Term 1', 2026, 'Tuition', 'UG6N8AI2DU', '2026-07-06 14:25:56'),
(27, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026173140419745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BBC4A1EDB6-4', '2026-07-06 14:31:38'),
(28, 1, 4, 9998.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026173208882745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BBC670F887-4', '2026-07-06 14:32:07'),
(29, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026173227982745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BBC7999B10-4', '2026-07-06 14:32:25'),
(30, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026174712259745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BBFEE0C271-4', '2026-07-06 14:47:10'),
(31, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026175347333745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BC1792E164-4', '2026-07-06 14:53:45'),
(32, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026180201106745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BC366AB08B-4', '2026-07-06 15:01:58'),
(33, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026180238799745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BC38C7F528-4', '2026-07-06 15:02:36'),
(34, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'failed', 'ws_CO_06072026180355817745959757', 'Term 1', 2026, 'Tuition', '', '2026-07-06 15:03:53'),
(35, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026180700777745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BC4920ABC5-4', '2026-07-06 15:06:58'),
(36, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026181305975745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BC5FF43952-4', '2026-07-06 15:13:03'),
(37, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'failed', 'ws_CO_06072026181349475745959757', 'Term 1', 2026, 'Tuition', 'UG6N8AI79R', '2026-07-06 15:13:46'),
(38, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'failed', 'ws_CO_06072026181938961745959757', 'Term 1', 2026, 'Tuition', 'UG6N8AIAGX', '2026-07-06 15:19:36'),
(39, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'pending', 'ws_CO_06072026182650228745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A4BC937A0293-4', '2026-07-06 15:26:47'),
(40, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'completed', 'ws_CO_06072026183827234745959757', 'Term 1', 2026, 'Tuition', 'UG6N8AIHB4', '2026-07-06 15:38:24'),
(42, 1, 4, 1.00, '2026-07-06', 'M-Pesa', 'completed', 'ws_CO_06072026183934251745959757', 'Term 1', 2026, 'RMADIAL', 'UG6N8AIET0', '2026-07-06 15:39:31'),
(45, 1, 4, 1.00, '2026-07-07', 'M-Pesa', 'completed', 'ws_CO_07072026134642480745959757', 'Term 1', 2026, 'Tuition', 'UG7N8ALA2Y', '2026-07-07 10:46:40'),
(46, 1, 4, 1.00, '2026-07-07', 'M-Pesa', 'completed', 'ws_CO_07072026234056220745959757', 'Term 1', 2026, 'Tuition', 'UG7N8ANIWC', '2026-07-07 20:40:54'),
(47, 1, 4, 1.00, '2026-07-08', 'M-Pesa', 'completed', 'ws_CO_TEST_1783460732', 'Term 1', 2026, 'Tuition', 'TEST1783460732', '2026-07-07 21:45:32'),
(48, 1, 4, 1.00, '2026-07-08', 'M-Pesa', 'completed', 'ws_CO_08072026004628902745959757', 'Term 1', 2026, 'Tuition', 'UG8N8ANLTG', '2026-07-07 21:46:26'),
(49, 1, 4, 1.00, '2026-07-08', 'M-Pesa', 'completed', 'ws_CO_08072026113856483745959757', 'Term 1', 2026, 'Tuition', 'UG8N8AOV3O', '2026-07-08 08:38:52'),
(51, 1, 4, 1.00, '2026-07-15', 'M-Pesa', 'completed', 'ws_CO_15072026011155751745959757', 'Term 1', 2026, 'Tuition', 'UGFN8BG3G5', '2026-07-14 22:11:53'),
(52, 1, 4, 1.00, '2026-07-16', 'M-Pesa', 'completed', 'ws_CO_16072026121807872745959757', 'Term 1', 2026, 'Tuition', 'UGGN8BL5Y8', '2026-07-16 09:18:06'),
(53, 1, 4, 2.00, '2026-07-16', 'M-Pesa', 'completed', 'ws_CO_16072026121906511745959757', 'Term 1', 2026, 'Tuition', 'UGGN8BL4JP', '2026-07-16 09:19:03'),
(55, 1, 4, 1.00, '2026-07-19', 'M-Pesa', 'completed', 'ws_CO_19072026162300421745959757', 'Term 1', 2026, 'Tuition', 'UGJN801DNO', '2026-07-19 13:22:56'),
(56, 1, 4, 2.00, '2026-07-19', 'M-Pesa', 'completed', 'ws_CO_19072026162337917745959757', 'Term 1', 2026, 'Tuition', 'UGJN801C80', '2026-07-19 13:23:34'),
(57, 1, 4, 2.00, '2026-07-19', 'M-Pesa', 'pending', 'ws_CO_19072026200130556745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A5D02E8A3038-4', '2026-07-19 17:01:28'),
(58, 1, 4, 2.00, '2026-07-19', 'M-Pesa', 'completed', 'ws_CO_19072026200320607745959757', 'Term 1', 2026, 'Tuition', 'UGJN802DVF', '2026-07-19 17:03:18'),
(59, 1, 4, 1.00, '2026-07-19', 'M-Pesa', 'completed', 'ws_CO_19072026231905970745959757', 'Term 1', 2026, 'Tuition', 'UGJN8032CK', '2026-07-19 20:19:04'),
(60, 1, 4, 1.00, '2026-07-20', 'M-Pesa', 'pending', 'ws_CO_20072026050232743745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A5D81B7B1C55-4', '2026-07-20 02:02:31'),
(61, 1, 4, 1.00, '2026-07-20', 'M-Pesa', 'pending', 'ws_CO_20072026050423172745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A5D8226392C9-4', '2026-07-20 02:04:22'),
(62, 1, 4, 1.00, '2026-07-20', 'M-Pesa', 'pending', 'ws_CO_20072026051045220745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A5D83A48037B-4', '2026-07-20 02:10:44'),
(63, 1, 4, 9983.00, '2026-07-20', 'M-Pesa', 'pending', 'ws_CO_20072026051128616745959757', 'Term 1', 2026, 'Tuition', 'FEE-6A5D83CF9B6EE-4', '2026-07-20 02:11:27'),
(67, 1, 4, 1.00, '2026-07-22', 'M-Pesa', 'completed', 'ws_CO_22072026225433145745959757', 'Term 1', 2026, 'Tuition', 'UGMN80EP8R', '2026-07-22 19:54:29'),
(70, 1, 4, 1.00, '2026-07-23', 'M-Pesa', 'completed', 'ws_CO_23072026192534871745959757', 'Term 1', 2026, 'Tuition', 'UGNN80HUQC', '2026-07-23 16:25:32'),
(71, 1, 4, 1.00, '2026-07-24', 'M-Pesa', 'pending', 'ws_CO_24072026012614195745959757', 'Term 2', 2026, 'Tuition', 'FEE-6A629504C0EE5-4', '2026-07-23 22:26:12'),
(72, 1, 4, 1.00, '2026-07-24', 'M-Pesa', 'pending', 'ws_CO_24072026012652605745959757', 'Term 2', 2026, 'Tuition', 'FEE-6A62952B03727-4', '2026-07-23 22:26:51'),
(73, 0, 4, 1.00, '2026-07-24', 'M-Pesa', 'pending', 'ws_CO_24072026012936815745959757', 'Term 2', 2026, 'Tuition', 'FEE-6A6295CF50B13-4', '2026-07-23 22:29:35'),
(74, 0, 4, 1.00, '2026-07-24', 'M-Pesa', 'pending', 'ws_CO_24072026013300460745959757', 'Term 2', 2026, 'Tuition', 'FEE-6A62969ACA0D5-4', '2026-07-23 22:32:58'),
(76, 0, 4, 1.00, '2026-07-24', 'M-Pesa', 'completed', 'ws_CO_24072026013732159745959757', 'Term 2', 2026, 'Tuition', 'UGON80IRKH', '2026-07-23 22:37:30');

-- --------------------------------------------------------

--
-- Table structure for table `fee_structure`
--

CREATE TABLE `fee_structure` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `term` varchar(50) NOT NULL,
  `year` int(4) NOT NULL,
  `fee_type` varchar(100) DEFAULT 'Tuition',
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_structure`
--

INSERT INTO `fee_structure` (`id`, `school_id`, `class_id`, `term`, `year`, `fee_type`, `amount`, `description`, `created_at`) VALUES
(2, 1, 1, 'Term 2', 2026, 'Tuition', 15000.00, 'THIS SI TERM 2 FEE', '2026-07-05 10:29:58'),
(3, 1, 1, 'Term 3', 2026, 'Tuition', 10000.00, 'THIS IS TERM 3 FEE\r\n', '2026-07-05 11:02:55'),
(4, 1, 1, 'Term 1', 2026, 'RMADIAL', 1000.00, 'THIS IS REMEDIAL FEE', '2026-07-05 14:42:56'),
(5, 1, 1, 'Term 1', 2026, 'Tuition', 21000.00, 'this is term 1 fee', '2026-07-22 20:18:28');

-- --------------------------------------------------------

--
-- Table structure for table `finance_managers`
--

CREATE TABLE `finance_managers` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `finance_managers`
--

INSERT INTO `finance_managers` (`id`, `school_id`, `first_name`, `last_name`, `email`, `phone`, `id_number`, `address`, `status`, `created_at`) VALUES
(1, 1, 'Brian', 'Onyango', 'otienobrian029@gmail.com', '0745959757', '40718992', 'Kisumu\r\n40100 kisumu', 'active', '2026-07-05 14:16:17');

-- --------------------------------------------------------

--
-- Table structure for table `finance_manager_logins`
--

CREATE TABLE `finance_manager_logins` (
  `id` int(11) NOT NULL,
  `finance_manager_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `finance_manager_logins`
--

INSERT INTO `finance_manager_logins` (`id`, `finance_manager_id`, `email`, `password`, `is_active`, `created_at`) VALUES
(1, 1, 'otienobrian029@gmail.com', '$2y$10$abixF.9wBTc7yj9aops9z.0Wf5TKGdundcshGzVX01Sq4.wrwE0Py', 1, '2026-07-05 14:16:17');

-- --------------------------------------------------------

--
-- Table structure for table `finance_manager_sessions`
--

CREATE TABLE `finance_manager_sessions` (
  `id` int(11) NOT NULL,
  `finance_manager_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `finance_manager_sessions`
--

INSERT INTO `finance_manager_sessions` (`id`, `finance_manager_id`, `session_token`, `expires_at`, `created_at`) VALUES
(1, 1, '7a3b1efec46d8e3d6c1cb8435ee66a68b0c2bbbef09b6af2d69e44c20ca4098d', '2026-07-06 00:16:35', '2026-07-05 14:16:35'),
(2, 1, '01485a22d1af3b2c1d40dbda80a0b0cdd4350f02ab8cac2f1158bf4b8ca81d77', '2026-07-06 03:39:53', '2026-07-05 17:39:53'),
(3, 1, 'eb16bf4570785a5b67c12487f98f9bd75fd077f5496ea524c0d574156d507d4c', '2026-07-08 06:16:00', '2026-07-07 20:16:00'),
(4, 1, '4390644a8803d31f7e0abfa6cc5316ed98db529273786dfa10054fc566412e1f', '2026-07-09 05:20:37', '2026-07-08 19:20:37'),
(5, 1, 'c835f9dd6045592c4d97b642706657d38aac2be649c1abcbe67a87084de1e69b', '2026-07-10 01:11:07', '2026-07-09 15:11:07'),
(6, 1, '73adcb731cca6ab7475975413acb227d47d2c8ce165f65adbcf09a2ca76c4f42', '2026-07-12 22:40:35', '2026-07-12 12:40:35'),
(7, 1, '8b0622b0aa6975758b38d3ca718acc7e31bd568aa1108f26148e7d02bccfa56f', '2026-07-15 07:59:54', '2026-07-14 21:59:54'),
(8, 1, '87d181ea6484991901e72cd62191e8e8fe62a310c4dac2a6bdb2f03e2d050788', '2026-07-16 16:31:28', '2026-07-16 06:31:28'),
(9, 1, 'bd5dce255efd23d6931b4997ac6eb02b81d17b068fe7b423e4ab98091c83a26b', '2026-07-20 03:05:21', '2026-07-19 17:05:21'),
(10, 1, '01ac277a201d297c5b0b403bb47f7d8828ea21fedd9f881f6210172d0e71f99a', '2026-07-21 06:20:25', '2026-07-20 20:20:25'),
(11, 1, 'be481b1fa660f9c720d7e884f8f1428dd45e56d83915fcc1a7e35c380cb4c536', '2026-07-24 07:27:33', '2026-07-23 21:27:33'),
(12, 1, 'e7383e7be492222e3c5ca7cc618919eb63294b567e92d72397d9390fe12f6389', '2026-07-24 08:03:25', '2026-07-23 22:03:25'),
(13, 1, '9390f5189cd4601be6918b8d5acdbb37fe0e447d21c3ef6e2034253047d74f9e', '2026-07-24 08:59:15', '2026-07-23 22:59:15'),
(14, 1, '8a5ea9554ef6d5846067e365e65f9c7d3e4d8e545040dce4031d0fca4ca7fccf', '2026-07-25 10:01:57', '2026-07-25 00:01:57');

-- --------------------------------------------------------

--
-- Table structure for table `grading_scales`
--

CREATE TABLE `grading_scales` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `min_score` int(11) NOT NULL,
  `max_score` int(11) NOT NULL,
  `grade_name` varchar(50) NOT NULL,
  `grade_description` text DEFAULT NULL,
  `points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grading_scales`
--

INSERT INTO `grading_scales` (`id`, `school_id`, `subject_id`, `min_score`, `max_score`, `grade_name`, `grade_description`, `points`, `created_at`) VALUES
(28, 1, 3, 0, 14, 'E', 'Fail', 1, '2026-07-19 13:54:30'),
(29, 1, 3, 15, 19, 'D-', 'Fail', 2, '2026-07-19 13:54:30'),
(30, 1, 3, 20, 24, 'D', 'Poor', 3, '2026-07-19 13:54:30'),
(31, 1, 3, 25, 29, 'D+', 'Fair', 4, '2026-07-19 13:54:30'),
(32, 1, 3, 30, 34, 'C-', 'Good', 5, '2026-07-19 13:54:30'),
(33, 1, 3, 35, 39, 'C', 'Good', 6, '2026-07-19 13:54:30'),
(34, 1, 3, 40, 44, 'C+', 'Very Good', 7, '2026-07-19 13:54:30'),
(35, 1, 3, 45, 49, 'B-', 'Exelent', 8, '2026-07-19 13:54:30'),
(36, 1, 3, 50, 54, 'B', 'Exelent', 9, '2026-07-19 13:54:30'),
(37, 1, 3, 55, 59, 'B+', 'Exelent', 10, '2026-07-19 13:54:30'),
(38, 1, 3, 60, 64, 'A-', 'Exelent', 11, '2026-07-19 13:54:30'),
(39, 1, 3, 65, 100, 'A', 'Exelent', 12, '2026-07-19 13:54:30'),
(40, 1, 2, 0, 9, 'E', 'Fail', 1, '2026-07-19 14:34:06'),
(41, 1, 2, 10, 14, 'D-', 'Fail', 2, '2026-07-19 14:34:06'),
(42, 1, 2, 15, 19, 'D', 'Poor', 3, '2026-07-19 14:34:06'),
(43, 1, 2, 20, 24, 'D+', 'Good', 4, '2026-07-19 14:34:06'),
(44, 1, 2, 25, 29, 'C-', 'Fair', 5, '2026-07-19 14:34:06'),
(45, 1, 2, 30, 34, 'C', 'Good', 6, '2026-07-19 14:34:06'),
(46, 1, 2, 35, 39, 'C+', 'Very Good', 7, '2026-07-19 14:34:06'),
(47, 1, 2, 40, 44, 'B-', 'Exelent', 8, '2026-07-19 14:34:06'),
(48, 1, 2, 45, 49, 'B', 'Exelent', 9, '2026-07-19 14:34:06'),
(49, 1, 2, 50, 54, 'B+', 'Exelent', 10, '2026-07-19 14:34:06'),
(50, 1, 2, 55, 59, 'A-', 'Exelent', 11, '2026-07-19 14:34:06'),
(51, 1, 2, 60, 100, 'A', 'Exelent', 12, '2026-07-19 14:34:06'),
(52, 1, 4, 0, 29, 'E', 'Fail', 1, '2026-07-24 20:13:54'),
(53, 1, 4, 30, 34, 'D-', 'Fail', 2, '2026-07-24 20:13:54'),
(54, 1, 4, 35, 39, 'D-', 'Poor', 3, '2026-07-24 20:13:54'),
(55, 1, 4, 40, 44, 'D+', 'Fair', 4, '2026-07-24 20:13:54'),
(56, 1, 4, 45, 49, 'C-', 'Good', 5, '2026-07-24 20:13:54'),
(57, 1, 4, 50, 54, 'C', 'Good', 6, '2026-07-24 20:13:54'),
(58, 1, 4, 55, 59, 'C+', 'Very Good', 7, '2026-07-24 20:13:54'),
(59, 1, 4, 60, 64, 'B-', 'Very Good', 8, '2026-07-24 20:13:54'),
(60, 1, 4, 65, 69, 'B', 'Excellent', 9, '2026-07-24 20:13:54'),
(61, 1, 4, 70, 74, 'B+', 'Excellent', 10, '2026-07-24 20:13:54'),
(62, 1, 4, 75, 79, 'A-', 'Excellent', 11, '2026-07-24 20:13:54'),
(63, 1, 4, 80, 100, 'A', 'Excellent', 12, '2026-07-24 20:13:54'),
(64, 1, 8, 0, 37, 'E', 'Fail', 1, '2026-07-24 20:24:48'),
(65, 1, 8, 38, 42, 'D-', 'Fail', 2, '2026-07-24 20:24:48'),
(66, 1, 8, 43, 47, 'D', 'Poor', 3, '2026-07-24 20:24:48'),
(67, 1, 8, 48, 52, 'D+', 'Fair', 4, '2026-07-24 20:24:48'),
(68, 1, 8, 53, 57, 'C-', 'Good', 5, '2026-07-24 20:24:48'),
(69, 1, 8, 58, 62, 'C', 'Good', 6, '2026-07-24 20:24:48'),
(70, 1, 8, 63, 67, 'C+', 'Very Good', 7, '2026-07-24 20:24:48'),
(71, 1, 8, 68, 72, 'B-', 'Very Good', 8, '2026-07-24 20:24:48'),
(72, 1, 8, 73, 77, 'B', 'Very Good', 9, '2026-07-24 20:24:48'),
(73, 1, 8, 78, 82, 'B+', 'Exelent', 10, '2026-07-24 20:24:48'),
(74, 1, 8, 83, 87, 'A-', 'Exelent', 11, '2026-07-24 20:24:48'),
(75, 1, 8, 88, 100, 'A', 'Exelent', 12, '2026-07-24 20:24:48'),
(76, 1, 12, 0, 27, 'E', 'Fail', 1, '2026-07-24 20:36:58'),
(77, 1, 12, 28, 32, 'D-', 'Fail', 2, '2026-07-24 20:36:58'),
(78, 1, 12, 33, 37, 'D', 'Poor', 3, '2026-07-24 20:36:58'),
(79, 1, 12, 38, 42, 'D+', 'Poor', 4, '2026-07-24 20:36:58'),
(80, 1, 12, 43, 47, 'C-', 'Good', 5, '2026-07-24 20:36:58'),
(81, 1, 12, 48, 52, 'C', 'Good', 6, '2026-07-24 20:36:58'),
(82, 1, 12, 53, 57, 'C+', 'Good', 7, '2026-07-24 20:36:58'),
(83, 1, 12, 58, 62, 'B-', 'Very Good', 8, '2026-07-24 20:36:58'),
(84, 1, 12, 63, 67, 'B', 'Very Good', 9, '2026-07-24 20:36:58'),
(85, 1, 12, 68, 72, 'B+', 'Exelent', 10, '2026-07-24 20:36:58'),
(86, 1, 12, 73, 77, 'A-', 'Exelent', 11, '2026-07-24 20:36:58'),
(87, 1, 12, 78, 100, 'A', 'Exelent', 12, '2026-07-24 20:36:58'),
(88, 1, 11, 0, 29, 'E', 'Fail', 1, '2026-07-24 20:47:03'),
(89, 1, 11, 30, 34, 'D-', 'Fail', 2, '2026-07-24 20:47:03'),
(90, 1, 11, 35, 39, 'D', 'Por', 3, '2026-07-24 20:47:03'),
(91, 1, 11, 40, 44, 'D+', 'Por', 4, '2026-07-24 20:47:03'),
(92, 1, 11, 45, 49, 'C-', 'Good', 5, '2026-07-24 20:47:03'),
(93, 1, 11, 50, 54, 'C', 'Good', 6, '2026-07-24 20:47:03'),
(94, 1, 11, 55, 59, 'C+', 'Good', 7, '2026-07-24 20:47:03'),
(95, 1, 11, 60, 64, 'B-', 'Very Good', 8, '2026-07-24 20:47:03'),
(96, 1, 11, 65, 69, 'B', 'Very Good', 9, '2026-07-24 20:47:03'),
(97, 1, 11, 70, 74, 'B+', 'Exelent', 10, '2026-07-24 20:47:03'),
(98, 1, 11, 75, 79, 'A-', 'Exelent', 11, '2026-07-24 20:47:03'),
(99, 1, 11, 80, 100, 'A', 'Exelent', 12, '2026-07-24 20:47:03'),
(100, 1, 6, 0, 11, 'E', 'Fail', 1, '2026-07-24 20:58:00'),
(101, 1, 6, 12, 18, 'D-', 'Fail', 2, '2026-07-24 20:58:00'),
(102, 1, 6, 19, 24, 'D', 'Poor', 3, '2026-07-24 20:58:00'),
(103, 1, 6, 25, 30, 'D+', 'Poor', 4, '2026-07-24 20:58:00'),
(104, 1, 6, 31, 36, 'C-', 'Good', 5, '2026-07-24 20:58:00'),
(105, 1, 6, 37, 42, 'C', 'Good', 6, '2026-07-24 20:58:00'),
(106, 1, 6, 43, 48, 'C+', 'Good', 7, '2026-07-24 20:58:00'),
(107, 1, 6, 49, 54, 'B-', 'Very Good', 8, '2026-07-24 20:58:00'),
(108, 1, 6, 55, 59, 'B', 'Very Good', 9, '2026-07-24 20:58:00'),
(109, 1, 6, 60, 64, 'B+', 'Exelent', 10, '2026-07-24 20:58:00'),
(110, 1, 6, 65, 69, 'A-', 'Exelent', 11, '2026-07-24 20:58:00'),
(111, 1, 6, 70, 100, 'A', 'Exelent', 12, '2026-07-24 20:58:00'),
(112, 1, 9, 0, 29, 'E', 'Fail', 1, '2026-07-24 21:08:55'),
(113, 1, 9, 30, 34, 'D-', 'Fail', 2, '2026-07-24 21:08:55'),
(114, 1, 9, 35, 39, 'D', 'Poor', 3, '2026-07-24 21:08:55'),
(115, 1, 9, 40, 44, 'D+', 'Poor', 4, '2026-07-24 21:08:55'),
(116, 1, 9, 45, 49, 'C-', 'Good', 5, '2026-07-24 21:08:55'),
(117, 1, 9, 50, 54, 'C', 'Good', 6, '2026-07-24 21:08:55'),
(118, 1, 9, 55, 59, 'C+', 'Good', 7, '2026-07-24 21:08:55'),
(119, 1, 9, 60, 64, 'B-', 'Very Good', 8, '2026-07-24 21:08:55'),
(120, 1, 9, 65, 69, 'B', 'Very Good', 9, '2026-07-24 21:08:55'),
(121, 1, 9, 70, 74, 'B+', 'Exelent', 10, '2026-07-24 21:08:55'),
(122, 1, 9, 75, 84, 'A-', 'Exelent', 11, '2026-07-24 21:08:55'),
(123, 1, 9, 85, 100, 'A', 'Exelent', 12, '2026-07-24 21:08:55'),
(124, 1, 5, 0, 15, 'E', 'Fail', 1, '2026-07-24 21:16:39'),
(125, 1, 5, 16, 20, 'D-', 'Fail', 2, '2026-07-24 21:16:39'),
(126, 1, 5, 21, 25, 'D', 'Poor', 3, '2026-07-24 21:16:39'),
(127, 1, 5, 26, 30, 'D+', 'Poor', 4, '2026-07-24 21:16:39'),
(128, 1, 5, 31, 35, 'C-', 'Good', 5, '2026-07-24 21:16:39'),
(129, 1, 5, 36, 40, 'C', 'Good', 6, '2026-07-24 21:16:39'),
(130, 1, 5, 41, 45, 'C+', 'Good', 7, '2026-07-24 21:16:39'),
(131, 1, 5, 46, 50, 'B-', 'Very Good', 8, '2026-07-24 21:16:39'),
(132, 1, 5, 51, 55, 'B', 'Very Good', 9, '2026-07-24 21:16:39'),
(133, 1, 5, 56, 60, 'B+', 'Exelent', 10, '2026-07-24 21:16:39'),
(134, 1, 5, 61, 65, 'A-', 'Exelent', 11, '2026-07-24 21:16:39'),
(135, 1, 5, 66, 100, 'A', 'Exelent', 12, '2026-07-24 21:16:39'),
(136, 1, 7, 0, 39, 'E', 'Fail', 1, '2026-07-24 21:29:28'),
(137, 1, 7, 40, 44, 'D-', 'Fail', 2, '2026-07-24 21:29:28'),
(138, 1, 7, 45, 49, 'D', 'Poor', 3, '2026-07-24 21:29:28'),
(139, 1, 7, 50, 54, 'D+', 'Poor', 4, '2026-07-24 21:29:28'),
(140, 1, 7, 55, 59, 'C-', 'Good', 5, '2026-07-24 21:29:28'),
(141, 1, 7, 60, 64, 'C', 'Good', 6, '2026-07-24 21:29:28'),
(142, 1, 7, 65, 65, 'C+', 'Good', 7, '2026-07-24 21:29:28'),
(143, 1, 7, 70, 74, 'B-', 'Very Good', 8, '2026-07-24 21:29:28'),
(144, 1, 7, 75, 79, 'B', 'Very Good', 9, '2026-07-24 21:29:28'),
(145, 1, 7, 80, 84, 'B+', 'Exelent', 10, '2026-07-24 21:29:28'),
(146, 1, 7, 85, 89, 'A-', 'Exelent', 11, '2026-07-24 21:29:28'),
(147, 1, 7, 90, 100, 'A', 'Exelent', 12, '2026-07-24 21:29:28'),
(148, 1, 10, 0, 14, 'E', 'Fail', 1, '2026-07-24 21:38:17'),
(149, 1, 10, 15, 19, 'D-', 'Fail', 2, '2026-07-24 21:38:17'),
(150, 1, 10, 20, 29, 'D', 'Poor', 3, '2026-07-24 21:38:17'),
(151, 1, 10, 30, 54, 'D+', 'Poor', 4, '2026-07-24 21:38:17'),
(152, 1, 10, 35, 39, 'C-', 'Good', 5, '2026-07-24 21:38:17'),
(153, 1, 10, 40, 44, 'C', 'Good', 6, '2026-07-24 21:38:17'),
(154, 1, 10, 45, 54, 'C+', 'Good', 7, '2026-07-24 21:38:17'),
(155, 1, 10, 55, 59, 'B-', 'Very Good', 8, '2026-07-24 21:38:17'),
(156, 1, 10, 60, 69, 'B', 'Very Good', 9, '2026-07-24 21:38:17'),
(157, 1, 10, 70, 74, 'B+', 'Exelent', 10, '2026-07-24 21:38:17'),
(158, 1, 10, 75, 79, 'A-', 'Exelent', 11, '2026-07-24 21:38:17'),
(159, 1, 10, 80, 100, 'A', 'Exelent', 12, '2026-07-24 21:38:17');

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `holiday_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `holiday_type` enum('public','school','religious','other') DEFAULT 'school',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `school_id`, `holiday_name`, `description`, `start_date`, `end_date`, `holiday_type`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'TERM I', 'TEST', '2026-04-04', '2026-04-26', 'school', 1, '2026-07-23 20:23:39', '2026-07-23 20:23:39');

-- --------------------------------------------------------

--
-- Table structure for table `incident_reports`
--

CREATE TABLE `incident_reports` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `incident_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `severity` varchar(20) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `term` varchar(50) NOT NULL,
  `year` int(4) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `balance_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','partial','paid','overdue','cancelled') DEFAULT 'pending',
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_number`, `school_id`, `student_id`, `class_id`, `term`, `year`, `total_amount`, `paid_amount`, `balance_amount`, `status`, `issue_date`, `due_date`, `description`, `created_at`, `updated_at`) VALUES
(3, 'SCH-202607-0001', 1, 4, 1, 'Term 1', 2026, 20000.00, 10008.00, 9992.00, 'partial', '2026-07-16', '2026-08-15', 'Tuition fee invoice for Term 1 2026', '2026-07-16 08:56:01', '2026-07-16 08:56:01');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `fee_structure_id` int(11) NOT NULL,
  `fee_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_payments`
--

CREATE TABLE `invoice_payments` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `allocated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_payments`
--

INSERT INTO `invoice_payments` (`id`, `invoice_id`, `payment_id`, `amount`, `allocated_at`) VALUES
(10, 3, 1, 10000.00, '2026-07-16 08:56:01'),
(11, 3, 26, 1.00, '2026-07-16 08:56:01'),
(12, 3, 40, 1.00, '2026-07-16 08:56:01'),
(13, 3, 45, 1.00, '2026-07-16 08:56:01'),
(14, 3, 46, 1.00, '2026-07-16 08:56:01'),
(15, 3, 47, 1.00, '2026-07-16 08:56:01'),
(16, 3, 48, 1.00, '2026-07-16 08:56:01'),
(17, 3, 49, 1.00, '2026-07-16 08:56:01'),
(18, 3, 51, 1.00, '2026-07-16 08:56:01');

-- --------------------------------------------------------

--
-- Table structure for table `leaveout_chits`
--

CREATE TABLE `leaveout_chits` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leaveout_chits`
--

INSERT INTO `leaveout_chits` (`id`, `school_id`, `teacher_id`, `student_id`, `reason`, `created_by`, `created_at`) VALUES
(1, 1, 1, 4, 'Medical appointment', 1, '2026-07-08 20:55:42'),
(2, 1, 1, 4, 'Medical appointment', 1, '2026-07-08 20:56:52');

-- --------------------------------------------------------

--
-- Table structure for table `librarians`
--

CREATE TABLE `librarians` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `librarians`
--

INSERT INTO `librarians` (`id`, `school_id`, `first_name`, `last_name`, `email`, `phone`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Brian', 'Onyango', 'otienobrian029@gmail.com', '0745959757', 'active', '2026-07-05 15:22:37', '2026-07-05 15:24:45');

-- --------------------------------------------------------

--
-- Table structure for table `librarian_logins`
--

CREATE TABLE `librarian_logins` (
  `id` int(11) NOT NULL,
  `librarian_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `librarian_logins`
--

INSERT INTO `librarian_logins` (`id`, `librarian_id`, `email`, `password`, `is_active`, `created_at`) VALUES
(1, 1, 'otienobrian029@gmail.com', '$2y$10$jutIFiiFwxuf79QlgDGSv.0/OEcly6pRx/TlQc8gTxaWrDq7FnAYS', 1, '2026-07-05 15:22:37');

-- --------------------------------------------------------

--
-- Table structure for table `librarian_sessions`
--

CREATE TABLE `librarian_sessions` (
  `id` int(11) NOT NULL,
  `librarian_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `librarian_sessions`
--

INSERT INTO `librarian_sessions` (`id`, `librarian_id`, `session_token`, `expires_at`, `created_at`) VALUES
(2, 1, 'b85815b05d9bf5ef7509ae940c128fef9c58871348b33f1b0059c0cf406d14e0', '2026-07-06 02:02:18', '2026-07-05 16:02:18'),
(3, 1, '1117c71b44894e86c01ac178d8b06fbc916fe900417c6d93719e4a659f3d4076', '2026-07-06 02:50:37', '2026-07-05 16:50:37'),
(4, 1, 'd72ab46a73f824e5f63865a07512b6698f5a60efb27d7f9314c6bea371ed0314', '2026-07-15 08:38:12', '2026-07-14 22:38:12'),
(5, 1, 'f1cfdca4e958faebbb021506fd2935f451f938b7fc31ccbd3d0b2a06abb23b06', '2026-07-16 16:36:32', '2026-07-16 06:36:32'),
(6, 1, '49feddd1ce03d2969205a501b8e1c7dfdc9e5d2a0ff6c2f49732420b103d8a85', '2026-07-21 06:20:05', '2026-07-20 20:20:05'),
(8, 1, 'b983a065fc682539104a54465dc29ace236532d5999159320e55a9b4897c7974', '2026-07-22 23:45:26', '2026-07-22 13:45:26'),
(9, 1, '0b33def5e0edc697db122c32b6249d5be35bdbfa69e1123d9f2bc2c6fa7d0291', '2026-07-24 08:36:12', '2026-07-23 22:36:12'),
(10, 1, 'c8c0b5d024dc9af9d87468752ea0d6e3bf88f8b65eb8b5e1d4748eb99fb3fe28', '2026-07-24 09:12:08', '2026-07-23 23:12:08'),
(11, 1, '9180e323dd7ec22a5b28fe879749b67a679ee474436c16912350cb99932fe38c', '2026-07-24 22:16:41', '2026-07-24 12:16:41'),
(12, 1, '0e119556302c021db4aa56ed1ffb851d040f01dda9a0a7c3ee8e7e48848807a9', '2026-07-25 04:14:20', '2026-07-24 18:14:20'),
(13, 1, 'c7608c2ddc4d511bf86dee7e45dc86c8bbe1e6c194381c2800b489cf86958f82', '2026-07-25 08:42:50', '2026-07-24 22:42:50');

-- --------------------------------------------------------

--
-- Table structure for table `library_fines`
--

CREATE TABLE `library_fines` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrowing_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('student','teacher') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `status` enum('unpaid','partial','paid','waived','pending') DEFAULT NULL,
  `issue_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `due_date` timestamp NULL DEFAULT NULL,
  `fine_type` varchar(20) DEFAULT 'overdue',
  `payment_date` timestamp NULL DEFAULT NULL,
  `payment_method` enum('cash','mpesa') DEFAULT 'cash',
  `transaction_reference` varchar(100) DEFAULT NULL,
  `receipt_number` varchar(255) DEFAULT NULL,
  `waiver_reason` text DEFAULT NULL,
  `waived_by` int(11) DEFAULT NULL,
  `waived_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `library_fines`
--

INSERT INTO `library_fines` (`id`, `school_id`, `book_id`, `borrowing_id`, `user_id`, `user_type`, `amount`, `amount_paid`, `status`, `issue_date`, `due_date`, `fine_type`, `payment_date`, `payment_method`, `transaction_reference`, `receipt_number`, `waiver_reason`, `waived_by`, `waived_date`) VALUES
(28, 1, 4, 8, 4, 'student', 125.00, 2.00, 'unpaid', '2026-07-24 18:27:47', '2026-08-23 18:27:47', 'damaged', '2026-07-24 23:50:40', NULL, 'ws_CO_25072026034015366745959757', 'UGPN80MX5K', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `relationship` enum('Father','Mother','Guardian') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parents`
--

INSERT INTO `parents` (`id`, `school_id`, `first_name`, `last_name`, `email`, `phone`, `id_number`, `address`, `relationship`, `created_at`) VALUES
(2, 1, 'SAMUEL', 'OKECH', 'otienobrian029@gmail.com', '0745959757', '40718992', 'Kisumu\n40100 kisumu', 'Father', '2026-07-04 15:52:29');

-- --------------------------------------------------------

--
-- Table structure for table `parent_logins`
--

CREATE TABLE `parent_logins` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parent_sessions`
--

CREATE TABLE `parent_sessions` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parent_sessions`
--

INSERT INTO `parent_sessions` (`id`, `parent_id`, `session_token`, `expires_at`, `created_at`) VALUES
(1, 2, '848eaf5f4f5f0a59fea47c75a5a63343ce3f062b06e47e0ee46980784ac94f95', '2026-07-05 18:11:05', '2026-07-05 08:11:05'),
(2, 2, 'ab6a1861ac59d8b61cebdb816193c756fb9fbe1fbf1e91b9d128b7ab45fc1830', '2026-07-05 18:16:15', '2026-07-05 08:16:15'),
(3, 2, 'b12135734996309b3c697fc51fe30fa104fd5ed156bdc5766d54ef8ad284b51d', '2026-07-06 02:05:52', '2026-07-05 16:05:52'),
(4, 2, 'd7913fd357a06426bd3a4a36f2646661bab1253b2fdf5dbc9e399bb296b7b109', '2026-07-06 03:13:06', '2026-07-05 17:13:06'),
(5, 2, '4eb4ce4f2d156a1470f39d5a250322db6ecdb18f0d22dfea42a3cedef7a5fbb7', '2026-07-06 22:36:10', '2026-07-06 12:36:10'),
(6, 2, 'f25376c862384d0f91c4588351be1d2499f8a6164ad6cf1abad6d948982ba81d', '2026-07-07 20:41:23', '2026-07-07 10:41:23'),
(7, 2, 'b5ed9e88608f6128b7e79237821dc7d495c9ec0578ca96e50c9819a912424d6f', '2026-07-08 05:21:10', '2026-07-07 19:21:10'),
(8, 2, '793f95a0912104061509f702cac4731ba88625bcc67d97cbe87181d240ad42f4', '2026-07-08 18:24:58', '2026-07-08 08:24:58'),
(9, 2, '6b1aa72948dcae0f742a10397795bb7ec5636e345ebb2cf854795d686224a0c6', '2026-07-09 05:19:20', '2026-07-08 19:19:20'),
(11, 2, '446a4d7600eafdba54bc25e03cc152a76d1ae209253b929a4acf2ea33999dfd4', '2026-07-15 07:36:11', '2026-07-14 21:36:11'),
(12, 2, 'c277bca542630118d03d01f61add7b6b1ed44e0c0dce5a47720eaec70bb2da8c', '2026-07-16 16:36:53', '2026-07-16 06:36:53'),
(13, 2, '76a430889e19485376b6df7ac3c1b2842ab67b64c3f528252299ce548f5ccf42', '2026-07-19 23:12:26', '2026-07-19 13:12:26'),
(14, 2, '4d0b2f65ffa32817dc5ee664c7d1a250746948c5a45e00e4c3fb6fd83377cad3', '2026-07-21 06:21:29', '2026-07-20 20:21:29'),
(15, 2, '5cc7b5877911f142ca20874eef5facce4ae8a87cb868db45e4aed959661f1a0d', '2026-07-22 09:50:07', '2026-07-21 23:50:07'),
(16, 2, '9bd64721cc5cc666f8ac487084c9e9d3eab13231be7ba10934d4a6b0af217832', '2026-07-22 14:03:39', '2026-07-22 04:03:39'),
(17, 2, '6c981d14f16c79e3e852e12fc420d7555ca30c51eacae3619501d8a856eba045', '2026-07-22 23:06:38', '2026-07-22 13:06:38'),
(18, 2, '234d772b2c5133790f01f42fca46f5744dfb82c51338d9622a3c8bb7f414d075', '2026-07-23 05:48:59', '2026-07-22 19:48:59'),
(19, 2, '7c0463e27e6e9537057f76a58fe89fc3fd8ac6e64a402cee5301f6ca8a6e3723', '2026-07-24 02:24:07', '2026-07-23 16:24:07'),
(20, 2, '162e9b096f6dd5b855c0e45918a052c0f732917279688ab457b353421aaa0341', '2026-07-24 08:25:53', '2026-07-23 22:25:53'),
(21, 2, '3361a763aaeed17adf0ebffd7d79877d50e60cc38cfeb1f7595bad2a7d884a99', '2026-07-25 05:56:49', '2026-07-24 19:56:49');

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
-- Table structure for table `reminder_history`
--

CREATE TABLE `reminder_history` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `term` varchar(50) NOT NULL,
  `outstanding_amount` decimal(10,2) NOT NULL,
  `reminder_type` enum('email','letter','manual') NOT NULL DEFAULT 'email',
  `message` text DEFAULT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reminder_history`
--

INSERT INTO `reminder_history` (`id`, `student_id`, `school_id`, `year`, `term`, `outstanding_amount`, `reminder_type`, `message`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 2026, 'Term 1', 9983.00, 'email', 'testing email notification', 'failed', '2026-07-20 00:08:30', '2026-07-20 00:08:30'),
(2, 4, 1, 2026, 'Term 1', 9983.00, 'email', 'testing email notification', 'failed', '2026-07-20 00:13:42', '2026-07-20 00:13:42'),
(3, 4, 1, 2026, 'Term 1', 9983.00, 'email', 'test', 'failed', '2026-07-20 00:14:02', '2026-07-20 00:14:02'),
(4, 4, 1, 2026, 'Term 1', 9983.00, 'email', 'test', 'sent', '2026-07-20 00:16:12', '2026-07-20 00:16:12'),
(5, 4, 1, 2026, 'Term 1', 9983.00, 'email', 'that is fee reminder', 'sent', '2026-07-20 00:23:30', '2026-07-20 00:23:30'),
(6, 4, 1, 2026, 'Term 1', 9983.00, 'email', '', 'sent', '2026-07-20 00:25:18', '2026-07-20 00:25:18'),
(7, 4, 1, 2026, 'Term 1', 9983.00, 'email', '', 'sent', '2026-07-20 00:25:36', '2026-07-20 00:25:36'),
(8, 4, 1, 2026, 'Term 1', 9983.00, 'email', '', 'sent', '2026-07-20 00:29:39', '2026-07-20 00:29:39'),
(9, 4, 1, 2026, 'Term 1', 9983.00, 'email', '', 'sent', '2026-07-20 00:29:43', '2026-07-20 00:29:43'),
(10, 4, 1, 2026, 'Term 1', 9983.00, 'email', '', 'sent', '2026-07-20 00:29:48', '2026-07-20 00:29:48'),
(11, 4, 1, 2026, 'Term 1', 9983.00, 'email', '', 'sent', '2026-07-20 00:31:23', '2026-07-20 00:31:23'),
(12, 4, 1, 2026, 'Term 1', 9983.00, 'email', '', 'sent', '2026-07-20 00:31:27', '2026-07-20 00:31:27'),
(13, 4, 1, 2026, 'Term 1', 9983.00, 'email', '', 'sent', '2026-07-20 00:31:33', '2026-07-20 00:31:33'),
(14, 4, 1, 2026, 'Term 1', 10981.00, 'email', '', 'sent', '2026-07-23 22:39:05', '2026-07-23 22:39:05');

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
(20, 'test', 'Secondary', 'Electrical principles', 'PDF', 'hbn jbnkm.,;\'m,klm', '69b029291de4f_LOOPTORRENT.docx', 7, '2026-03-10 14:22:33', '1be5e82cbf76368955f5dec380630c44', 1),
(21, 'jhvnm', 'Primary', 'Electrical principles', 'PDF', 'jbkm m, m,', 'api/uploads/69b02ae8cc597_KENAS CONSTRUCTION AND RENOVATION LTD.docx', 3, '2026-03-10 14:30:00', 'f8b1944b7de9189b0d4081c0d7cfee46', 3),
(22, 'poj0jiojpoi', 'Primary', 'Electrical principles', 'PDF', ' , ,  j n  m, ,knknklnklnkl', 'api/uploads/69b02cd108a90_https.docx', 3, '2026-03-10 14:38:09', '140803be555067a953debe486ceae1b1', 1),
(23, 'test 2', 'Primary', 'Electrical principles', 'DOC', 'nm nm, m,. ,.nkl ,.', 'api/uploads/69b02da23d284_PROJECT.docx', 6, '2026-03-10 14:41:38', 'a0499cd64ba631411e8122a7d859ff20', 3),
(24, 'test 3', 'secondary', 'Electrical principles', 'pdf', 'klnjkrvjjkb , jk', 'api/uploads/69b02e8d4925f_Brian Onyango 2.docx', 9, '2026-03-10 14:45:33', 'af5ab37ef45a68afc078fc26c9d86320', 1),
(25, 'DOMESTIC WATER SUPLY', 'College', 'Solar installation', 'PDF', 'this is domestic water  suply notes pdf', 'api/uploads/69b17e3fcad9e_DOMESTIC WATER SYPPLLY  I     LEARNING OUTLINE.pdf', 44, '2026-03-11 14:37:51', 'd6bb6412e21d0f055cb63343119c6de7', 3),
(26, 'TEST 4', 'Primary', 'Electrical principles', 'PDF', ';HIOE;CKLCN,/C', 'api/uploads/69b17fa0b3a29_p5.pdf', 14, '2026-03-11 14:43:44', '5cda01173befa3a6e23ea23e7d52a4ac', 1),
(27, 'TEST 5', 'College', 'Electrical principles', 'pdf', 'IHJKNJCNWLKNLNC', 'api/uploads/69b181d343036_LEARNING-GUIDE-FOR-BASIC-COMPETENCIES-LEVEL-6 (1).pdf', 30, '2026-03-11 14:53:07', 'be3f5070e42052bfa63b3b4a9ea171da', 3),
(28, 'TEST6', 'College', 'Electrical principles', 'PDF', 'KGV,JN NMNKLIYHGFUCJHNM/UIG', 'api/uploads/69b572d05757b_downloaded_1760873391530.pdf', 15, '2026-03-14 14:38:08', 'd41d8cd98f00b204e9800998ecf8427e', 1),
(29, 'TEST 7', 'Primary', 'Electrical principles', 'PDF', 'YCVKJKCNJKHUIVKHFNKLNVWKLV', 'api/uploads/69b5ac121c0a2_downloaded_1765453715140.pdf', 6, '2026-03-14 18:42:26', '61725cf198f2491fdb1abcc7fc90d58a', 3),
(31, 'EUIFEJIFBNEJKV', 'Primary', 'Electrical principles', 'PDF', 'JINJKL JBJIKNMLN', 'api/uploads/69ba510e7d103_EXTRACTED.pdf', 4, '2026-03-18 07:15:26', '6d34e8b2f851bfaf3a17126862463951', 1),
(32, 'JHVJHJKN', 'Primary', 'Electrical principles', 'PDF', 'VJK/BLB/KLJBNKLN', 'api/uploads/69bd5f25971cd_test 3.pdf', 5, '2026-03-20 14:52:21', '426ea864277f44c2135e05c22229a9eb', 26),
(33, 'UIGJK', 'Primary', 'Solar installation', 'PDF', 'JIJIKBLJKNKLNKL', 'api/uploads/69bd5f692154a_defensive.docx', 4, '2026-03-20 14:53:29', '3ef1a27964e7a5bbdf30e88ccfc57d92', 26);

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` int(11) NOT NULL,
  `school_code` varchar(20) NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `county` varchar(100) NOT NULL,
  `school_type` enum('Primary','Secondary','College','University') NOT NULL,
  `admission_prefix` varchar(50) DEFAULT NULL,
  `status` enum('pending','active','suspended') DEFAULT 'pending',
  `logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `school_code`, `school_name`, `email`, `password`, `phone`, `address`, `county`, `school_type`, `admission_prefix`, `status`, `logo`, `created_at`, `updated_at`) VALUES
(1, 'SCH176C0AD1', 'NDERE SENIOR SCHOOL', 'otisbrian46@gmail.com', '$2y$10$cVlyR0SCbJTqz7twg0/a2.VVdmROdKmDt.8A5kgi.f8Bm1yEK.6oG', '0745959757', 'Kisumu\n40100 kisumu', 'HOMABAY', 'Secondary', 'NDS', 'active', '../uploads/schools/school_1_1783185722.png', '2026-07-04 15:06:57', '2026-07-04 17:25:35'),
(2, 'SCH1432B168', 'WIOBIERO SENIOR SCHOOL', 'otienobrian029@gmail.com', '$2y$10$QyUnDPtCEiqQpcX7uPOCruiTGtM64pNztG20LBJS7/v9snfRz.eTu', '0745959757', 'Kisumu\n40100 kisumu', 'SIAYA', 'Primary', 'NDS', 'active', NULL, '2026-07-23 22:45:36', '2026-07-23 22:48:32');

-- --------------------------------------------------------

--
-- Table structure for table `school_b2c_responses`
--

CREATE TABLE `school_b2c_responses` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_b2c_responses`
--

INSERT INTO `school_b2c_responses` (`id`, `withdrawal_id`, `callback_type`, `result_code`, `result_desc`, `originator_conversation_id`, `conversation_id`, `transaction_id`, `transaction_amount`, `receiver_party`, `transaction_completed_at`, `raw_response`, `created_at`) VALUES
(1, 6, 'result', '2040', 'Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .', 'c6fe-4c50-848a-aeda3881d02211', 'AG_20260709_0100103718mikj6ae3yu', 'UG90000000', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":2040,\"ResultDesc\":\"Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .\",\"OriginatorConversationID\":\"c6fe-4c50-848a-aeda3881d02211\",\"ConversationID\":\"AG_20260709_0100103718mikj6ae3yu\",\"TransactionID\":\"UG90000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-09 17:49:43'),
(2, 7, 'result', '2040', 'Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .', '458f-4c59-86c9-6b9348359b4328597', 'AG_20260712_010010030xlsx3qv45vq', 'UGC0000000', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":2040,\"ResultDesc\":\"Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4328597\",\"ConversationID\":\"AG_20260712_010010030xlsx3qv45vq\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 12:41:18'),
(3, 8, 'result', '2040', 'Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .', '458f-4c59-86c9-6b9348359b4328612', 'AG_20260712_010010060xm61xrobdyn', 'UGC0000000', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":2040,\"ResultDesc\":\"Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4328612\",\"ConversationID\":\"AG_20260712_010010060xm61xrobdyn\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 12:41:35'),
(4, 9, 'result', '2040', 'Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .', '458f-4c59-86c9-6b9348359b4328842', 'AG_20260712_010010370xszfoqvkj18', 'UGC0000000', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":2040,\"ResultDesc\":\"Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4328842\",\"ConversationID\":\"AG_20260712_010010370xszfoqvkj18\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 12:46:53'),
(5, 10, 'result', '7', 'The ReceiverParty information is invalid.', '4311-46f6-9a91-011b31669b70152307', 'AG_20260712_010010090xvdtwhb5m15', 'UGC0907OD3', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":7,\"ResultDesc\":\"The ReceiverParty information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70152307\",\"ConversationID\":\"AG_20260712_010010090xvdtwhb5m15\",\"TransactionID\":\"UGC0907OD3\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 12:48:45'),
(6, 11, 'result', '7', 'The ReceiverParty information is invalid.', '4311-46f6-9a91-011b31669b70152526', 'AG_20260712_010010330y03yhoy6ul6', 'UGC0X03E5X', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":7,\"ResultDesc\":\"The ReceiverParty information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70152526\",\"ConversationID\":\"AG_20260712_010010330y03yhoy6ul6\",\"TransactionID\":\"UGC0X03E5X\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 12:52:25'),
(7, 12, 'result', '7', 'The ReceiverParty information is invalid.', '4311-46f6-9a91-011b31669b70152603', 'AG_20260712_010010090y2r36wms494', 'UGC0907OD4', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":7,\"ResultDesc\":\"The ReceiverParty information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70152603\",\"ConversationID\":\"AG_20260712_010010090y2r36wms494\",\"TransactionID\":\"UGC0907OD4\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 12:54:29'),
(8, 13, 'result', '7', 'The ReceiverParty information is invalid.', '4311-46f6-9a91-011b31669b70152742', 'AG_20260712_010010150y5knhmysqvh', 'UGC0F06E05', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":7,\"ResultDesc\":\"The ReceiverParty information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70152742\",\"ConversationID\":\"AG_20260712_010010150y5knhmysqvh\",\"TransactionID\":\"UGC0F06E05\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 12:56:40'),
(9, 14, 'result', '2001', 'The initiator information is invalid.', '458f-4c59-86c9-6b9348359b4329651', 'AG_20260712_010010030ye6s6aobu6c', 'UGC030DL5G', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4329651\",\"ConversationID\":\"AG_20260712_010010030ye6s6aobu6c\",\"TransactionID\":\"UGC030DL5G\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 13:03:23'),
(10, 15, 'result', '2001', 'The initiator information is invalid.', '4311-46f6-9a91-011b31669b70153288', 'AG_20260712_010010030yga79fmuqwz', 'UGC030DL5H', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70153288\",\"ConversationID\":\"AG_20260712_010010030yga79fmuqwz\",\"TransactionID\":\"UGC030DL5H\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 13:05:00'),
(11, 16, 'result', '2001', 'The initiator information is invalid.', '4311-46f6-9a91-011b31669b70154141', 'AG_20260712_010010030yxlg6myxi2v', 'UGC030DJK7', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70154141\",\"ConversationID\":\"AG_20260712_010010030yxlg6myxi2v\",\"TransactionID\":\"UGC030DJK7\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 13:18:28'),
(12, 19, 'result', '2001', 'The initiator information is invalid.', '4311-46f6-9a91-011b31669b70154453', 'AG_20260712_010010030z43tx1qrrp1', 'UGC030DJK8', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70154453\",\"ConversationID\":\"AG_20260712_010010030z43tx1qrrp1\",\"TransactionID\":\"UGC030DJK8\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 13:23:35'),
(13, 20, 'result', '2001', 'The initiator information is invalid.', '458f-4c59-86c9-6b9348359b4330592', 'AG_20260712_010010030z63xyu21r6b', 'UGC030DJK9', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4330592\",\"ConversationID\":\"AG_20260712_010010030z63xyu21r6b\",\"TransactionID\":\"UGC030DJK9\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 13:25:05'),
(14, 21, 'result', '8006', 'The security credential is locked.', '458f-4c59-86c9-6b9348359b4330735', 'AG_20260712_010010030z9eubmye52b', 'UGC030DJKA', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4330735\",\"ConversationID\":\"AG_20260712_010010030z9eubmye52b\",\"TransactionID\":\"UGC030DJKA\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 13:27:39'),
(15, 22, 'result', '8006', 'The security credential is locked.', '4311-46f6-9a91-011b31669b70155080', 'AG_20260712_010010030zftpfo3ay3f', 'UGC030DJKC', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70155080\",\"ConversationID\":\"AG_20260712_010010030zftpfo3ay3f\",\"TransactionID\":\"UGC030DJKC\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 13:32:39'),
(16, 23, 'result', '8006', 'The security credential is locked.', '4311-46f6-9a91-011b31669b70155176', 'AG_20260712_010010030zhagfgjtg5s', 'UGC030DJKD', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70155176\",\"ConversationID\":\"AG_20260712_010010030zhagfgjtg5s\",\"TransactionID\":\"UGC030DJKD\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 13:33:47'),
(17, 24, 'result', '8006', 'The security credential is locked.', '4311-46f6-9a91-011b31669b70156464', 'AG_20260712_0100100310a3jz8p29s1', 'UGC030DJKE', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70156464\",\"ConversationID\":\"AG_20260712_0100100310a3jz8p29s1\",\"TransactionID\":\"UGC030DJKE\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 13:56:11'),
(18, 25, 'result', '8006', 'The security credential is locked.', '458f-4c59-86c9-6b9348359b4332860', 'AG_20260712_0100100310g908rz6htr', 'UGC030DJKH', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4332860\",\"ConversationID\":\"AG_20260712_0100100310g908rz6htr\",\"TransactionID\":\"UGC030DJKH\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 14:00:58'),
(19, 26, 'result', '8006', 'The security credential is locked.', '4311-46f6-9a91-011b31669b70156977', 'AG_20260712_0100100310kmn2uz3j35', 'UGC030DL5N', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70156977\",\"ConversationID\":\"AG_20260712_0100100310kmn2uz3j35\",\"TransactionID\":\"UGC030DL5N\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 14:04:22'),
(20, 27, 'result', '8006', 'The security credential is locked.', '4311-46f6-9a91-011b31669b70158461', 'AG_20260712_0100100311e3sib7qumz', 'UGC030DL5O', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70158461\",\"ConversationID\":\"AG_20260712_0100100311e3sib7qumz\",\"TransactionID\":\"UGC030DL5O\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 14:27:17'),
(21, 28, 'result', '8006', 'The security credential is locked.', '458f-4c59-86c9-6b9348359b4335394', 'AG_20260712_0100100312b8x6r03cbf', 'UGC030DL5Q', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4335394\",\"ConversationID\":\"AG_20260712_0100100312b8x6r03cbf\",\"TransactionID\":\"UGC030DL5Q\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 14:53:06'),
(22, 29, 'result', '8006', 'The security credential is locked.', '4311-46f6-9a91-011b31669b70161381', 'AG_20260712_010010031321bsgc5y61', 'UGC030DJKP', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70161381\",\"ConversationID\":\"AG_20260712_010010031321bsgc5y61\",\"TransactionID\":\"UGC030DJKP\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 15:13:54'),
(23, 30, 'result', '8006', 'The security credential is locked.', '4311-46f6-9a91-011b31669b70162365', 'AG_20260712_0100100313mtebcmhlh4', 'UGC030DL5V', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70162365\",\"ConversationID\":\"AG_20260712_0100100313mtebcmhlh4\",\"TransactionID\":\"UGC030DL5V\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 15:30:03'),
(24, 31, 'result', '4001', 'Insufficient balance', '4311-46f6-9a91-011b31669b70162534', 'AG_20260712_0100100313rinqkn2x41', 'UGC0000000', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70162534\",\"ConversationID\":\"AG_20260712_0100100313rinqkn2x41\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 15:33:42'),
(25, 32, 'result', '4001', 'Insufficient balance', '4311-46f6-9a91-011b31669b70162580', 'AG_20260712_0100100313sdyhs8jx90', 'UGC0000000', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70162580\",\"ConversationID\":\"AG_20260712_0100100313sdyhs8jx90\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 15:34:24'),
(26, 33, 'result', '4001', 'Insufficient balance', '458f-4c59-86c9-6b9348359b4337854', 'AG_20260712_0100100314k229w74l1e', 'UGC0000000', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4337854\",\"ConversationID\":\"AG_20260712_0100100314k229w74l1e\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 15:55:56'),
(27, 34, 'result', '4001', 'Insufficient balance', '4311-46f6-9a91-011b31669b70163860', 'AG_20260712_0100100314kykha6f5zm', 'UGC0000000', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70163860\",\"ConversationID\":\"AG_20260712_0100100314kykha6f5zm\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 15:56:36'),
(28, 37, 'result', '4001', 'Insufficient balance', '4311-46f6-9a91-011b31669b70166588', 'AG_20260712_0100100316s3pshk8cxg', 'UGC0000000', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70166588\",\"ConversationID\":\"AG_20260712_0100100316s3pshk8cxg\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 16:58:08'),
(29, 38, 'result', '4001', 'Insufficient balance', '4311-46f6-9a91-011b31669b70166855', 'AG_20260712_0100100316ziy0r7g334', 'UGC0000000', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70166855\",\"ConversationID\":\"AG_20260712_0100100316ziy0r7g334\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-12 17:03:55'),
(30, 40, 'result', '4001', 'Insufficient balance', 'ff3e-4fa4-abc0-8eb3aa92c0d9110087', 'AG_20260715_0100100301s7pngznnhy', 'UGF0000000', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"ff3e-4fa4-abc0-8eb3aa92c0d9110087\",\"ConversationID\":\"AG_20260715_0100100301s7pngznnhy\",\"TransactionID\":\"UGF0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-14 21:50:30'),
(31, 41, 'result', '4001', 'Insufficient balance', '3a62-4214-aa55-1c05a6d85a1c29486', 'AG_20260715_0100100302ighd81jdj9', 'UGF0000000', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"3a62-4214-aa55-1c05a6d85a1c29486\",\"ConversationID\":\"AG_20260715_0100100302ighd81jdj9\",\"TransactionID\":\"UGF0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-14 22:10:54'),
(54, NULL, 'result', '', '', '', '', NULL, NULL, NULL, NULL, '', '2026-07-19 20:40:11'),
(55, 62, 'result', '4001', 'Insufficient balance', '7cd0-4a28-8d04-cb27fe4732c675739', 'AG_20260720_0100100303magp3v5e6s', 'UGK0000000', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"7cd0-4a28-8d04-cb27fe4732c675739\",\"ConversationID\":\"AG_20260720_0100100303magp3v5e6s\",\"TransactionID\":\"UGK0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-19 22:41:59'),
(56, 63, 'result', '4001', 'Insufficient balance', '6839-428d-8589-2bcb00296f3788158', 'AG_20260720_010010030ay9icuyg8xg', 'UGK0000000', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"6839-428d-8589-2bcb00296f3788158\",\"ConversationID\":\"AG_20260720_010010030ay9icuyg8xg\",\"TransactionID\":\"UGK0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-20 02:07:15'),
(57, 64, 'result', '4001', 'Insufficient balance', '6839-428d-8589-2bcb00296f3788315', 'AG_20260720_010010030b29th2u5c1g', 'UGK0000000', NULL, NULL, NULL, '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"6839-428d-8589-2bcb00296f3788315\",\"ConversationID\":\"AG_20260720_010010030b29th2u5c1g\",\"TransactionID\":\"UGK0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-20 02:10:22'),
(58, 76, 'result', '0', 'The service request is processed successfully.', '6e47-4967-bcb9-74445bede7d2149876', 'AG_20260725_0100100306hfuhjt5vaj', 'UGP030DS9I', 10.00, '254708374149 - John Doe', '25.07.2026 03:01:31', '{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2149876\",\"ConversationID\":\"AG_20260725_0100100306hfuhjt5vaj\",\"TransactionID\":\"UGP030DS9I\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DS9I\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:01:31\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020629.44},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-25 00:02:17'),
(59, 77, 'result', '0', 'The service request is processed successfully.', '6e47-4967-bcb9-74445bede7d2149944', 'AG_20260725_0100100306isnutdzh5z', 'UGP030DTRC', 10.00, '254708374149 - John Doe', '25.07.2026 03:02:35', '{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2149944\",\"ConversationID\":\"AG_20260725_0100100306isnutdzh5z\",\"TransactionID\":\"UGP030DTRC\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRC\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:02:35\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020585.84},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-25 00:03:20'),
(60, 78, 'result', '0', 'The service request is processed successfully.', 'b13d-4e1d-8fb5-0f0d66c4323015398', 'AG_20260725_0100100306lgkp3uxieq', 'UGP030DS9J', 10.00, '254708374149 - John Doe', '25.07.2026 03:04:39', '{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"b13d-4e1d-8fb5-0f0d66c4323015398\",\"ConversationID\":\"AG_20260725_0100100306lgkp3uxieq\",\"TransactionID\":\"UGP030DS9J\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DS9J\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:04:39\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020542.24},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-25 00:05:24'),
(61, 79, 'result', '0', 'The service request is processed successfully.', '6e47-4967-bcb9-74445bede7d2150262', 'AG_20260725_0100100306pdhg6hp4zb', 'UGP030DTRD', 10.00, '254708374149 - John Doe', '25.07.2026 03:07:41', '{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2150262\",\"ConversationID\":\"AG_20260725_0100100306pdhg6hp4zb\",\"TransactionID\":\"UGP030DTRD\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRD\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:07:41\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020498.64},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-25 00:08:27'),
(62, 80, 'result', '0', 'The service request is processed successfully.', 'b13d-4e1d-8fb5-0f0d66c4323015602', 'AG_20260725_0100100306smeff2m19e', 'UGP030DS9L', 10.00, '254708374149 - John Doe', '25.07.2026 03:10:13', '{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"b13d-4e1d-8fb5-0f0d66c4323015602\",\"ConversationID\":\"AG_20260725_0100100306smeff2m19e\",\"TransactionID\":\"UGP030DS9L\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DS9L\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:10:13\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020455.04},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-25 00:10:59'),
(63, 81, 'result', '0', 'The service request is processed successfully.', 'b13d-4e1d-8fb5-0f0d66c4323015718', 'AG_20260725_0100100306vs08jfd9dp', 'UGP030DTRE', 10.00, '254708374149 - John Doe', '25.07.2026 03:12:40', '{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"b13d-4e1d-8fb5-0f0d66c4323015718\",\"ConversationID\":\"AG_20260725_0100100306vs08jfd9dp\",\"TransactionID\":\"UGP030DTRE\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRE\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:12:40\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020411.44},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-25 00:13:26'),
(64, 82, 'result', '0', 'The service request is processed successfully.', '6e47-4967-bcb9-74445bede7d2150843', 'AG_20260725_01001003072oz2xm4nf9', 'UGP030DS9N', 10.00, '254708374149 - John Doe', '25.07.2026 03:18:03', '{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2150843\",\"ConversationID\":\"AG_20260725_01001003072oz2xm4nf9\",\"TransactionID\":\"UGP030DS9N\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DS9N\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:18:03\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020367.84},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-25 00:18:48'),
(65, 83, 'result', '0', 'The service request is processed successfully.', 'b13d-4e1d-8fb5-0f0d66c4323016055', 'AG_20260725_01001003077xj8a055yi', 'UGP030DTRI', 10.00, '254708374149 - John Doe', '25.07.2026 03:22:07', '{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"b13d-4e1d-8fb5-0f0d66c4323016055\",\"ConversationID\":\"AG_20260725_01001003077xj8a055yi\",\"TransactionID\":\"UGP030DTRI\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRI\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:22:07\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020324.24},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-25 00:22:53'),
(66, 84, 'result', '0', 'The service request is processed successfully.', '6e47-4967-bcb9-74445bede7d2151316', 'AG_20260725_0100100307cpjvnuq7c3', 'UGP030DTRJ', 10.00, '254708374149 - John Doe', '25.07.2026 03:25:50', '{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2151316\",\"ConversationID\":\"AG_20260725_0100100307cpjvnuq7c3\",\"TransactionID\":\"UGP030DTRJ\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRJ\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:25:50\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020280.64},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-25 00:26:36');

-- --------------------------------------------------------

--
-- Table structure for table `school_balances`
--

CREATE TABLE `school_balances` (
  `school_id` int(11) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='School account balances';

--
-- Dumping data for table `school_balances`
--

INSERT INTO `school_balances` (`school_id`, `balance`, `created_at`, `updated_at`) VALUES
(1, 473.00, '2026-07-07 21:25:30', '2026-07-25 00:26:36');

-- --------------------------------------------------------

--
-- Table structure for table `school_breaks`
--

CREATE TABLE `school_breaks` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `break_name` varchar(100) NOT NULL,
  `break_type` enum('short_break','lunch_break','recess','other') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_breaks`
--

INSERT INTO `school_breaks` (`id`, `school_id`, `break_name`, `break_type`, `start_time`, `end_time`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Uji Break', 'short_break', '10:00:00', '10:30:00', 1, '2026-07-23 17:09:26', '2026-07-23 17:09:26'),
(3, 1, 'Lunch', 'lunch_break', '13:00:00', '13:59:00', 1, '2026-07-23 18:28:40', '2026-07-23 18:28:40');

-- --------------------------------------------------------

--
-- Table structure for table `school_events`
--

CREATE TABLE `school_events` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `event_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `event_type` enum('exam','meeting','sports','cultural','other') DEFAULT 'other',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school_sessions`
--

CREATE TABLE `school_sessions` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_sessions`
--

INSERT INTO `school_sessions` (`id`, `school_id`, `session_token`, `expires_at`, `created_at`) VALUES
(1, 1, '79a554b89d7faa5497e5366a948b63e1ebafddcac6592013dbf4bc38916883ca', '2026-07-05 17:07:26', '2026-07-04 15:07:26'),
(2, 1, '0b32a4c367bb6dde86aeb1b5539bc4f29534d1a09a0e32ea4a9b61b4873492d2', '2026-07-05 20:30:53', '2026-07-04 18:30:53'),
(3, 1, 'e46f2d6963369e9ea14fb8ea86884b1443b3710f1021543fb9cdc284960645d2', '2026-07-06 09:47:22', '2026-07-05 07:47:23'),
(4, 1, 'de32517c33b19bc744410230496fd4ab8808b331460d1eb4f222e893d25acb01', '2026-07-06 09:59:45', '2026-07-05 07:59:45'),
(5, 1, 'e2efd128737d6df7090cf4b8b910ebc76c98b6bb07ea9490268adaa73582da0c', '2026-07-06 18:49:35', '2026-07-05 16:49:35'),
(6, 1, '3754666bdc07457555b6ed2b55582b679dce1715ba3daf21a0ef1d8c7c355f34', '2026-07-23 04:19:29', '2026-07-22 02:19:29'),
(7, 1, '275dd60dee6354ba8875610f0bec1aed39c2e9027fc6adf77f26cb5b1d359cf7', '2026-07-23 14:47:33', '2026-07-22 12:47:33'),
(8, 1, 'd219e5defdc2aa5543a09f243042881538b139767f836efc58fca7e167c6223d', '2026-07-23 14:57:52', '2026-07-22 12:57:52'),
(9, 1, '953cb4254f0e21f94f574da8c3ce9af5374f3ab12e6d7941b8a0c0ce177fdc34', '2026-07-23 17:37:49', '2026-07-22 15:37:49'),
(10, 1, '482036542cd8bc09cbbcdc2f9ff0c64381ab5bf78677b587c1d7e17bdc52e53f', '2026-07-24 16:26:06', '2026-07-23 14:26:06'),
(11, 2, 'afac73761082daf3cca490511453dfe544c019e448c8c237c45cb3863f729524', '2026-07-25 00:47:36', '2026-07-23 22:47:36'),
(12, 2, '7760f9238729bc3ab29f159038c424fdbc91ccb09db0c75af85bbdb0fb147781', '2026-07-25 00:49:24', '2026-07-23 22:49:24'),
(13, 2, '2ee31a70e280242c2b61d99d832dfc72b383f86b68299b65e3f1b2e07a9c25e4', '2026-07-25 00:51:27', '2026-07-23 22:51:27'),
(14, 1, 'f9d21adb3614d8f976a58b03d06ee16ad6cf6188034f8da3dddf0c5c3fc5e32c', '2026-07-25 00:53:13', '2026-07-23 22:53:13'),
(15, 2, '2836dde3f778df1ff28fad662881c33b8f80040e75c64f62cb5e9a2eef2404ec', '2026-07-25 20:38:04', '2026-07-24 18:38:04'),
(16, 1, '905f63a583ea67080e3d0e2d102b7adbcb7a2ee65a1c639cec1a6542793ca142', '2026-07-25 20:38:18', '2026-07-24 18:38:18');

-- --------------------------------------------------------

--
-- Table structure for table `school_withdrawals`
--

CREATE TABLE `school_withdrawals` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `finance_manager_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `destination_type` varchar(30) NOT NULL,
  `destination_name` varchar(150) DEFAULT NULL,
  `destination_account` varchar(150) NOT NULL,
  `destination_extra` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('completed','pending','failed') NOT NULL DEFAULT 'completed',
  `reference_number` varchar(60) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `result_desc` text DEFAULT NULL,
  `originator_conversation_id` varchar(100) DEFAULT NULL,
  `conversation_id` varchar(100) DEFAULT NULL,
  `mpesa_receipt_number` varchar(100) DEFAULT NULL,
  `result_code` varchar(20) DEFAULT NULL,
  `callback_payload` longtext DEFAULT NULL,
  `success_at` timestamp NULL DEFAULT NULL,
  `balance_deducted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_withdrawals`
--

INSERT INTO `school_withdrawals` (`id`, `school_id`, `finance_manager_id`, `amount`, `destination_type`, `destination_name`, `destination_account`, `destination_extra`, `notes`, `status`, `reference_number`, `created_at`, `result_desc`, `originator_conversation_id`, `conversation_id`, `mpesa_receipt_number`, `result_code`, `callback_payload`, `success_at`, `balance_deducted_at`) VALUES
(1, 1, 1, 10.00, 'phone', 'Samwel okech', '254745959757', 'for testing', 'testing', 'failed', 'WDR-20260709194002-1-231', '2026-07-09 17:40:02', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'originator_conversation_id\' in \'field list\'', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 1, 1, 10.00, 'phone', 'Samwel okech', '254745959757', 'for testing', 'test', 'failed', 'WDR-20260709194218-1-699', '2026-07-09 17:42:18', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'originator_conversation_id\' in \'field list\'', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 1, 1, 10.00, 'phone', 'Samwel okech', '254745959757', 'for testing', 'test', 'failed', 'WDR-20260709194318-1-831', '2026-07-09 17:43:18', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'conversation_id\' in \'field list\'', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 1, 1, 10.00, 'phone', 'Samwel okech', '254745959757', 'for testing', 'test', 'failed', 'WDR-20260709194349-1-281', '2026-07-09 17:43:49', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'conversation_id\' in \'field list\'', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 1, 1, 10.00, 'phone', 'Samwel okech', '254745959757', 'for testing', 'testing', 'failed', 'WDR-20260709194842-1-823', '2026-07-09 17:48:42', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'callback_payload\' in \'field list\'', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 1, 1, 10.00, 'phone', 'Samwel okech', '254745959757', 'for testing', 'testing', 'failed', 'WDR-20260709194940-1-634', '2026-07-09 17:49:40', 'Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .', 'c6fe-4c50-848a-aeda3881d02211', 'AG_20260709_0100103718mikj6ae3yu', NULL, '2040', '{\"Result\":{\"ResultType\":0,\"ResultCode\":2040,\"ResultDesc\":\"Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .\",\"OriginatorConversationID\":\"c6fe-4c50-848a-aeda3881d02211\",\"ConversationID\":\"AG_20260709_0100103718mikj6ae3yu\",\"TransactionID\":\"UG90000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(7, 1, 1, 10.00, 'phone', 'Samwel okech', '254745959757', 'for testing', 'TEST', 'failed', 'WDR-20260712144114-1-633', '2026-07-12 12:41:14', 'Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .', '458f-4c59-86c9-6b9348359b4328597', 'AG_20260712_010010030xlsx3qv45vq', NULL, '2040', '{\"Result\":{\"ResultType\":0,\"ResultCode\":2040,\"ResultDesc\":\"Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4328597\",\"ConversationID\":\"AG_20260712_010010030xlsx3qv45vq\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(8, 1, 1, 10.00, 'phone', 'Samwel okech', '254745959757', 'for testing', 'TEST', 'failed', 'WDR-20260712144132-1-846', '2026-07-12 12:41:32', 'Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .', '458f-4c59-86c9-6b9348359b4328612', 'AG_20260712_010010060xm61xrobdyn', NULL, '2040', '{\"Result\":{\"ResultType\":0,\"ResultCode\":2040,\"ResultDesc\":\"Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4328612\",\"ConversationID\":\"AG_20260712_010010060xm61xrobdyn\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(9, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254745959757', 'for testing', 'TEST', 'failed', 'WDR-20260712144649-1-216', '2026-07-12 12:46:49', 'Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .', '458f-4c59-86c9-6b9348359b4328842', 'AG_20260712_010010370xszfoqvkj18', NULL, '2040', '{\"Result\":{\"ResultType\":0,\"ResultCode\":2040,\"ResultDesc\":\"Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4328842\",\"ConversationID\":\"AG_20260712_010010370xszfoqvkj18\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(10, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254745959757', 'for testing', 'TEST', 'failed', 'WDR-20260712144841-1-581', '2026-07-12 12:48:41', 'The ReceiverParty information is invalid.', '4311-46f6-9a91-011b31669b70152307', 'AG_20260712_010010090xvdtwhb5m15', NULL, '7', '{\"Result\":{\"ResultType\":0,\"ResultCode\":7,\"ResultDesc\":\"The ReceiverParty information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70152307\",\"ConversationID\":\"AG_20260712_010010090xvdtwhb5m15\",\"TransactionID\":\"UGC0907OD3\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(11, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254745959757', 'for testing', 'TEST', 'failed', 'WDR-20260712145222-1-942', '2026-07-12 12:52:22', 'The ReceiverParty information is invalid.', '4311-46f6-9a91-011b31669b70152526', 'AG_20260712_010010330y03yhoy6ul6', NULL, '7', '{\"Result\":{\"ResultType\":0,\"ResultCode\":7,\"ResultDesc\":\"The ReceiverParty information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70152526\",\"ConversationID\":\"AG_20260712_010010330y03yhoy6ul6\",\"TransactionID\":\"UGC0X03E5X\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(12, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254745959757', 'for testing', 'TEST', 'failed', 'WDR-20260712145425-1-571', '2026-07-12 12:54:25', 'The ReceiverParty information is invalid.', '4311-46f6-9a91-011b31669b70152603', 'AG_20260712_010010090y2r36wms494', NULL, '7', '{\"Result\":{\"ResultType\":0,\"ResultCode\":7,\"ResultDesc\":\"The ReceiverParty information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70152603\",\"ConversationID\":\"AG_20260712_010010090y2r36wms494\",\"TransactionID\":\"UGC0907OD4\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(13, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254745959757', 'for testing', 'TEST', 'failed', 'WDR-20260712145637-1-606', '2026-07-12 12:56:37', 'The ReceiverParty information is invalid.', '4311-46f6-9a91-011b31669b70152742', 'AG_20260712_010010150y5knhmysqvh', NULL, '7', '{\"Result\":{\"ResultType\":0,\"ResultCode\":7,\"ResultDesc\":\"The ReceiverParty information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70152742\",\"ConversationID\":\"AG_20260712_010010150y5knhmysqvh\",\"TransactionID\":\"UGC0F06E05\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(14, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TESTING', 'failed', 'WDR-20260712150319-1-870', '2026-07-12 13:03:19', 'The initiator information is invalid.', '458f-4c59-86c9-6b9348359b4329651', 'AG_20260712_010010030ye6s6aobu6c', NULL, '2001', '{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4329651\",\"ConversationID\":\"AG_20260712_010010030ye6s6aobu6c\",\"TransactionID\":\"UGC030DL5G\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(15, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'failed', 'WDR-20260712150457-1-576', '2026-07-12 13:04:57', 'The initiator information is invalid.', '4311-46f6-9a91-011b31669b70153288', 'AG_20260712_010010030yga79fmuqwz', NULL, '2001', '{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70153288\",\"ConversationID\":\"AG_20260712_010010030yga79fmuqwz\",\"TransactionID\":\"UGC030DL5H\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(16, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'failed', 'WDR-20260712151824-1-470', '2026-07-12 13:18:24', 'The initiator information is invalid.', '4311-46f6-9a91-011b31669b70154141', 'AG_20260712_010010030yxlg6myxi2v', NULL, '2001', '{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70154141\",\"ConversationID\":\"AG_20260712_010010030yxlg6myxi2v\",\"TransactionID\":\"UGC030DJK7\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(17, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'failed', 'WDR-20260712152033-1-782', '2026-07-12 13:20:33', 'Failed to get M-Pesa access token.', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'failed', 'WDR-20260712152047-1-327', '2026-07-12 13:20:47', 'Failed to get M-Pesa access token.', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TESTING', 'failed', 'WDR-20260712152328-1-780', '2026-07-12 13:23:28', 'The initiator information is invalid.', '4311-46f6-9a91-011b31669b70154453', 'AG_20260712_010010030z43tx1qrrp1', NULL, '2001', '{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70154453\",\"ConversationID\":\"AG_20260712_010010030z43tx1qrrp1\",\"TransactionID\":\"UGC030DJK8\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(20, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'failed', 'WDR-20260712152501-1-191', '2026-07-12 13:25:01', 'The initiator information is invalid.', '458f-4c59-86c9-6b9348359b4330592', 'AG_20260712_010010030z63xyu21r6b', NULL, '2001', '{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4330592\",\"ConversationID\":\"AG_20260712_010010030z63xyu21r6b\",\"TransactionID\":\"UGC030DJK9\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(21, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'failed', 'WDR-20260712152736-1-348', '2026-07-12 13:27:36', 'The security credential is locked.', '458f-4c59-86c9-6b9348359b4330735', 'AG_20260712_010010030z9eubmye52b', NULL, '8006', '{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4330735\",\"ConversationID\":\"AG_20260712_010010030z9eubmye52b\",\"TransactionID\":\"UGC030DJKA\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(22, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'failed', 'WDR-20260712153235-1-572', '2026-07-12 13:32:35', 'The security credential is locked.', '4311-46f6-9a91-011b31669b70155080', 'AG_20260712_010010030zftpfo3ay3f', NULL, '8006', '{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70155080\",\"ConversationID\":\"AG_20260712_010010030zftpfo3ay3f\",\"TransactionID\":\"UGC030DJKC\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(23, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'failed', 'WDR-20260712153342-1-370', '2026-07-12 13:33:42', 'The security credential is locked.', '4311-46f6-9a91-011b31669b70155176', 'AG_20260712_010010030zhagfgjtg5s', NULL, '8006', '{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70155176\",\"ConversationID\":\"AG_20260712_010010030zhagfgjtg5s\",\"TransactionID\":\"UGC030DJKD\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(26, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'test', 'failed', 'WDR-20260712160414-1-143', '2026-07-12 14:04:14', 'The security credential is locked.', '4311-46f6-9a91-011b31669b70156977', 'AG_20260712_0100100310kmn2uz3j35', NULL, '8006', '{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70156977\",\"ConversationID\":\"AG_20260712_0100100310kmn2uz3j35\",\"TransactionID\":\"UGC030DL5N\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(27, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'test', 'failed', 'WDR-20260712162714-1-828', '2026-07-12 14:27:14', 'The security credential is locked.', '4311-46f6-9a91-011b31669b70158461', 'AG_20260712_0100100311e3sib7qumz', NULL, '8006', '{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70158461\",\"ConversationID\":\"AG_20260712_0100100311e3sib7qumz\",\"TransactionID\":\"UGC030DL5O\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(28, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'test', 'failed', 'WDR-20260712165256-1-930', '2026-07-12 14:52:56', 'The security credential is locked.', '458f-4c59-86c9-6b9348359b4335394', 'AG_20260712_0100100312b8x6r03cbf', NULL, '8006', '{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4335394\",\"ConversationID\":\"AG_20260712_0100100312b8x6r03cbf\",\"TransactionID\":\"UGC030DL5Q\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(29, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'test', 'failed', 'WDR-20260712171347-1-529', '2026-07-12 15:13:47', 'The security credential is locked.', '4311-46f6-9a91-011b31669b70161381', 'AG_20260712_010010031321bsgc5y61', NULL, '8006', '{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70161381\",\"ConversationID\":\"AG_20260712_010010031321bsgc5y61\",\"TransactionID\":\"UGC030DJKP\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(30, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'failed', 'WDR-20260712172958-1-746', '2026-07-12 15:29:58', 'The security credential is locked.', '4311-46f6-9a91-011b31669b70162365', 'AG_20260712_0100100313mtebcmhlh4', NULL, '8006', '{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70162365\",\"ConversationID\":\"AG_20260712_0100100313mtebcmhlh4\",\"TransactionID\":\"UGC030DL5V\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(31, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'failed', 'WDR-20260712173338-1-784', '2026-07-12 15:33:38', 'Insufficient balance', '4311-46f6-9a91-011b31669b70162534', 'AG_20260712_0100100313rinqkn2x41', NULL, '4001', '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70162534\",\"ConversationID\":\"AG_20260712_0100100313rinqkn2x41\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(32, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'failed', 'WDR-20260712173419-1-341', '2026-07-12 15:34:19', 'Insufficient balance', '4311-46f6-9a91-011b31669b70162580', 'AG_20260712_0100100313sdyhs8jx90', NULL, '4001', '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70162580\",\"ConversationID\":\"AG_20260712_0100100313sdyhs8jx90\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(33, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'failed', 'WDR-20260712175542-1-908', '2026-07-12 15:55:42', 'Insufficient balance', '458f-4c59-86c9-6b9348359b4337854', 'AG_20260712_0100100314k229w74l1e', NULL, '4001', '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4337854\",\"ConversationID\":\"AG_20260712_0100100314k229w74l1e\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(34, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'failed', 'WDR-20260712175629-1-652', '2026-07-12 15:56:29', 'Insufficient balance', '4311-46f6-9a91-011b31669b70163860', 'AG_20260712_0100100314kykha6f5zm', NULL, '4001', '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70163860\",\"ConversationID\":\"AG_20260712_0100100314kykha6f5zm\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(35, 1, NULL, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'test', 'failed', 'WDR-20260712185342-1-715', '2026-07-12 16:53:42', 'Manually marked as failed to free up available balance', '4311-46f6-9a91-011b31669b70166344', 'AG_20260712_0100100316mj1zg0ulj8', NULL, NULL, NULL, NULL, NULL),
(36, 1, NULL, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'test', 'failed', 'WDR-20260712185659-1-244', '2026-07-12 16:56:59', 'M-Pesa B2C connection error: Failed to connect to sandbox.safaricom.co.ke port 443 after 21038 ms: Couldn\'t connect to server', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(37, 1, NULL, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'test', 'failed', 'WDR-20260712185800-1-237', '2026-07-12 16:58:00', 'Insufficient balance', '4311-46f6-9a91-011b31669b70166588', 'AG_20260712_0100100316s3pshk8cxg', NULL, '4001', '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70166588\",\"ConversationID\":\"AG_20260712_0100100316s3pshk8cxg\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(38, 1, NULL, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'test', 'failed', 'WDR-20260712190335-1-249', '2026-07-12 17:03:35', 'Insufficient balance', '4311-46f6-9a91-011b31669b70166855', 'AG_20260712_0100100316ziy0r7g334', NULL, '4001', '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70166855\",\"ConversationID\":\"AG_20260712_0100100316ziy0r7g334\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(39, 1, 1, 10.00, 'till', 'NDERE SENIOR SCHOOL', '4071899', 'for testing', 'test', 'failed', 'WDR-20260712191612-1-241', '2026-07-12 17:16:12', 'Manually marked as failed to free up available balance', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(40, 1, NULL, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'test', 'failed', 'WDR-20260714235027-1-640', '2026-07-14 21:50:27', 'Insufficient balance', 'ff3e-4fa4-abc0-8eb3aa92c0d9110087', 'AG_20260715_0100100301s7pngznnhy', NULL, '4001', '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"ff3e-4fa4-abc0-8eb3aa92c0d9110087\",\"ConversationID\":\"AG_20260715_0100100301s7pngznnhy\",\"TransactionID\":\"UGF0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(41, 1, NULL, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'failed', 'WDR-20260715001051-1-510', '2026-07-14 22:10:51', 'Insufficient balance', '3a62-4214-aa55-1c05a6d85a1c29486', 'AG_20260715_0100100302ighd81jdj9', NULL, '4001', '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"3a62-4214-aa55-1c05a6d85a1c29486\",\"ConversationID\":\"AG_20260715_0100100302ighd81jdj9\",\"TransactionID\":\"UGF0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(42, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'test', 'failed', 'WDR-20260719192524-1-645', '2026-07-19 17:25:24', 'Manually marked as failed to free up available balance', '7cd0-4a28-8d04-cb27fe4732c656325', 'AG_20260719_0100100317r35su95l4f', NULL, NULL, NULL, NULL, NULL),
(62, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'failed', 'WDR-20260720004156-1-859', '2026-07-19 22:41:56', 'Insufficient balance', '7cd0-4a28-8d04-cb27fe4732c675739', 'AG_20260720_0100100303magp3v5e6s', NULL, '4001', '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"7cd0-4a28-8d04-cb27fe4732c675739\",\"ConversationID\":\"AG_20260720_0100100303magp3v5e6s\",\"TransactionID\":\"UGK0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(63, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'test', 'testing', 'failed', 'WDR-20260720040712-1-863', '2026-07-20 02:07:12', 'Insufficient balance', '6839-428d-8589-2bcb00296f3788158', 'AG_20260720_010010030ay9icuyg8xg', NULL, '4001', '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"6839-428d-8589-2bcb00296f3788158\",\"ConversationID\":\"AG_20260720_010010030ay9icuyg8xg\",\"TransactionID\":\"UGK0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(64, 1, NULL, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'test', 'failed', 'WDR-20260720041019-1-108', '2026-07-20 02:10:19', 'Insufficient balance', '6839-428d-8589-2bcb00296f3788315', 'AG_20260720_010010030b29th2u5c1g', NULL, '4001', '{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"6839-428d-8589-2bcb00296f3788315\",\"ConversationID\":\"AG_20260720_010010030b29th2u5c1g\",\"TransactionID\":\"UGK0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', NULL, NULL),
(65, 1, NULL, 50.00, 'library_fine_payment', 'Library Fine Payment', 'TEST-1784734565', NULL, 'Fine payment for book ID: 1, Fine ID: 5', 'completed', 'TEST-1784734565', '2026-07-22 15:36:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(66, 1, NULL, 1.00, 'library_fine_payment', 'Library Fine Payment', 'UGMN80E49M', NULL, 'Fine payment for book ID: 4, Fine ID: 6', 'completed', 'UGMN80E49M', '2026-07-22 16:40:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(67, 1, NULL, 1.00, 'library_fine_payment', 'Library Fine Payment', 'UGMN80DZXL', NULL, 'Fine payment for book ID: 4, Fine ID: 6', 'completed', 'UGMN80DZXL', '2026-07-22 16:42:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(68, 1, NULL, 1.00, 'library_fine_payment', 'Library Fine Payment', 'UGMN80E4ND', NULL, 'Fine payment for book ID: 4, Fine ID: 6', 'completed', 'UGMN80E4ND', '2026-07-22 16:51:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(69, 1, NULL, 1.00, 'library_fine_payment', 'Library Fine Payment', 'UGMN80ELXU', NULL, 'Fine payment for book ID: 4, Fine ID: 6', 'completed', 'UGMN80ELXU', '2026-07-22 19:14:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(70, 1, NULL, 1.00, 'library_fine_payment', 'Library Fine Payment', 'UGMN80ES0M', NULL, 'Fine payment for book ID: 4, Fine ID: 6', 'completed', 'UGMN80ES0M', '2026-07-22 19:30:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(71, 1, NULL, 1.00, 'library_fine_payment', 'Library Fine Payment', 'UGMN80ES2Y', NULL, 'Fine payment for book ID: 4, Fine ID: 6', 'completed', 'UGMN80ES2Y', '2026-07-22 19:35:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(73, 1, NULL, 1.00, 'library_fine_payment', 'Library Fine Payment', 'UGON80IRKO', NULL, 'Fine payment for book ID: 4, Fine ID: 6', 'completed', 'UGON80IRKO', '2026-07-23 22:40:18', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(74, 1, NULL, 1.00, 'library_fine_payment', 'Library Fine Payment', 'UGON80L1VF', NULL, 'Fine payment for book ID: 4, Fine ID: 26', 'completed', 'UGON80L1VF', '2026-07-24 13:28:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(75, 1, NULL, 1.00, 'library_fine_payment', 'Library Fine Payment', 'UGPN80MX5K', NULL, 'Fine payment for book ID: 4, Fine ID: 28', 'completed', 'UGPN80MX5K', '2026-07-24 23:50:40', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(76, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', '', 'WDR-20260725020213-1-587', '2026-07-25 00:02:13', 'The service request is processed successfully.', '6e47-4967-bcb9-74445bede7d2149876', 'AG_20260725_0100100306hfuhjt5vaj', 'UGP030DS9I', '0', '{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2149876\",\"ConversationID\":\"AG_20260725_0100100306hfuhjt5vaj\",\"TransactionID\":\"UGP030DS9I\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DS9I\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:01:31\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020629.44},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-25 00:02:17', '2026-07-25 00:02:17'),
(77, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', '', 'WDR-20260725020316-1-645', '2026-07-25 00:03:16', 'The service request is processed successfully.', '6e47-4967-bcb9-74445bede7d2149944', 'AG_20260725_0100100306isnutdzh5z', 'UGP030DTRC', '0', '{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2149944\",\"ConversationID\":\"AG_20260725_0100100306isnutdzh5z\",\"TransactionID\":\"UGP030DTRC\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRC\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:02:35\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020585.84},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-25 00:03:20', '2026-07-25 00:03:20'),
(78, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', '', 'WDR-20260725020521-1-535', '2026-07-25 00:05:21', 'The service request is processed successfully.', 'b13d-4e1d-8fb5-0f0d66c4323015398', 'AG_20260725_0100100306lgkp3uxieq', 'UGP030DS9J', '0', '{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"b13d-4e1d-8fb5-0f0d66c4323015398\",\"ConversationID\":\"AG_20260725_0100100306lgkp3uxieq\",\"TransactionID\":\"UGP030DS9J\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DS9J\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:04:39\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020542.24},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-25 00:05:24', '2026-07-25 00:05:24'),
(79, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', '', 'WDR-20260725020823-1-789', '2026-07-25 00:08:23', 'The service request is processed successfully.', '6e47-4967-bcb9-74445bede7d2150262', 'AG_20260725_0100100306pdhg6hp4zb', 'UGP030DTRD', '0', '{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2150262\",\"ConversationID\":\"AG_20260725_0100100306pdhg6hp4zb\",\"TransactionID\":\"UGP030DTRD\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRD\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:07:41\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020498.64},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-25 00:08:27', '2026-07-25 00:08:27'),
(80, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'completed', 'WDR-20260725021055-1-871', '2026-07-25 00:10:55', 'The service request is processed successfully.', 'b13d-4e1d-8fb5-0f0d66c4323015602', 'AG_20260725_0100100306smeff2m19e', 'UGP030DS9L', '0', '{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"b13d-4e1d-8fb5-0f0d66c4323015602\",\"ConversationID\":\"AG_20260725_0100100306smeff2m19e\",\"TransactionID\":\"UGP030DS9L\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DS9L\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:10:13\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020455.04},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-25 00:19:27', '2026-07-25 00:19:27'),
(81, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TESTING', '', 'WDR-20260725021322-1-500', '2026-07-25 00:13:22', 'The service request is processed successfully.', 'b13d-4e1d-8fb5-0f0d66c4323015718', 'AG_20260725_0100100306vs08jfd9dp', 'UGP030DTRE', '0', '{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"b13d-4e1d-8fb5-0f0d66c4323015718\",\"ConversationID\":\"AG_20260725_0100100306vs08jfd9dp\",\"TransactionID\":\"UGP030DTRE\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRE\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:12:40\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020411.44},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-25 00:13:26', '2026-07-25 00:13:26'),
(82, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'completed', 'WDR-20260725021845-1-829', '2026-07-25 00:18:45', 'The service request is processed successfully.', '6e47-4967-bcb9-74445bede7d2150843', 'AG_20260725_01001003072oz2xm4nf9', 'UGP030DS9N', '0', '{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2150843\",\"ConversationID\":\"AG_20260725_01001003072oz2xm4nf9\",\"TransactionID\":\"UGP030DS9N\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DS9N\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:18:03\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020367.84},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-25 00:18:48', '2026-07-25 00:18:48'),
(83, 1, NULL, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'completed', 'WDR-20260725022249-1-473', '2026-07-25 00:22:49', 'The service request is processed successfully.', 'b13d-4e1d-8fb5-0f0d66c4323016055', 'AG_20260725_01001003077xj8a055yi', 'UGP030DTRI', '0', '{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"b13d-4e1d-8fb5-0f0d66c4323016055\",\"ConversationID\":\"AG_20260725_01001003077xj8a055yi\",\"TransactionID\":\"UGP030DTRI\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRI\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:22:07\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020324.24},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-25 00:22:53', '2026-07-25 00:22:53'),
(84, 1, 1, 10.00, 'phone', 'NDERE SENIOR SCHOOL', '254708374149', 'for testing', 'TEST', 'completed', 'WDR-20260725022632-1-945', '2026-07-25 00:26:32', 'The service request is processed successfully.', '6e47-4967-bcb9-74445bede7d2151316', 'AG_20260725_0100100307cpjvnuq7c3', 'UGP030DTRJ', '0', '{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2151316\",\"ConversationID\":\"AG_20260725_0100100307cpjvnuq7c3\",\"TransactionID\":\"UGP030DTRJ\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRJ\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:25:50\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020280.64},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}', '2026-07-25 00:26:36', '2026-07-25 00:26:36');

-- --------------------------------------------------------

--
-- Table structure for table `smtp_settings`
--

CREATE TABLE `smtp_settings` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `app_password` varchar(255) NOT NULL,
  `smtp_host` varchar(255) DEFAULT 'smtp.gmail.com',
  `smtp_port` int(11) DEFAULT 587,
  `encryption` varchar(10) DEFAULT 'tls',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `smtp_settings`
--

INSERT INTO `smtp_settings` (`id`, `school_id`, `email`, `app_password`, `smtp_host`, `smtp_port`, `encryption`, `created_at`, `updated_at`) VALUES
(1, 1, 'otienobrian029@gmail.com', 'dwuunoftzkodeome', 'smtp.gmail.com', 587, 'tls', '2026-07-19 16:05:06', '2026-07-19 16:05:08');

-- --------------------------------------------------------

--
-- Table structure for table `streams`
--

CREATE TABLE `streams` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `stream_name` varchar(50) NOT NULL,
  `capacity` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `streams`
--

INSERT INTO `streams` (`id`, `class_id`, `stream_name`, `capacity`, `created_at`) VALUES
(1, 1, 'EAST', 80, '2026-07-04 15:11:19'),
(2, 1, 'WEST', 80, '2026-07-04 15:55:18'),
(3, 1, 'NORTH', 80, '2026-07-04 15:55:40'),
(4, 1, 'SOUTH', 80, '2026-07-04 15:55:55'),
(5, 2, 'EAST', 80, '2026-07-05 19:02:20'),
(6, 2, 'WEST', 80, '2026-07-05 19:02:51'),
(7, 2, 'NORTH', 80, '2026-07-05 19:03:20'),
(8, 2, 'SOUTH', 80, '2026-07-05 19:04:04');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `admission_number` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `date_of_birth` date NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `stream_id` int(11) DEFAULT NULL,
  `admission_date` date NOT NULL,
  `status` enum('active','inactive','transferred','graduated') DEFAULT 'active',
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `school_id`, `admission_number`, `email`, `first_name`, `last_name`, `gender`, `date_of_birth`, `class_id`, `stream_id`, `admission_date`, `status`, `photo`, `created_at`, `updated_at`) VALUES
(4, 1, 'NDS/1', NULL, 'BRIAN ONYANGO', 'OTIENO', 'Male', '2002-10-15', 1, 1, '2026-07-04', 'active', NULL, '2026-07-04 15:49:24', '2026-07-04 15:49:24');

-- --------------------------------------------------------

--
-- Table structure for table `student_parents`
--

CREATE TABLE `student_parents` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_parents`
--

INSERT INTO `student_parents` (`id`, `student_id`, `parent_id`, `is_primary`, `created_at`) VALUES
(1, 4, 2, 1, '2026-07-04 15:52:29');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `school_id`, `subject_name`, `subject_code`, `status`, `created_at`) VALUES
(2, 1, 'PHYSICS', '001', 'active', '2026-07-04 17:09:30'),
(3, 1, 'CHEMISTRY', '002', 'active', '2026-07-05 19:04:39'),
(4, 1, 'BIOLOGY', '003', 'active', '2026-07-05 19:05:06'),
(5, 1, 'GEOGRAPHY', '004', 'active', '2026-07-05 19:05:32'),
(6, 1, 'MATHEMATICS', '005', 'active', '2026-07-05 19:06:29'),
(7, 1, 'CRE', '006', 'active', '2026-07-05 19:07:07'),
(8, 1, 'AGRICULTURE', '007', 'active', '2026-07-05 19:07:25'),
(9, 1, 'BUSINESSES STUDIES', '008', 'active', '2026-07-05 19:08:04'),
(10, 1, 'HISTORY AND GOVERNMENT', '009', 'active', '2026-07-05 19:08:37'),
(11, 1, 'ENGLISH', '010', 'active', '2026-07-05 19:09:13'),
(12, 1, 'KISWAHILI', '011', 'active', '2026-07-05 19:09:42');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `stream_id` int(11) DEFAULT NULL,
  `teacher_type` enum('class_teacher','subject_teacher') DEFAULT 'subject_teacher',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `school_id`, `class_id`, `stream_id`, `teacher_type`, `first_name`, `last_name`, `email`, `phone`, `id_number`, `address`, `subject`, `status`, `created_at`) VALUES
(1, 1, 1, 1, 'class_teacher', 'ROBINSON', 'OMOLLO', 'otienobrian029@gmail.com', '0745959757', '40718992', 'Kisumu\r\n40100 kisumu', 'PHYSICS', 'active', '2026-07-04 16:46:26');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_logins`
--

CREATE TABLE `teacher_logins` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_logins`
--

INSERT INTO `teacher_logins` (`id`, `teacher_id`, `email`, `password`, `is_active`, `created_at`) VALUES
(1, 1, 'otienobrian029@gmail.com', '$2y$10$gYVB/ChfPTZDykMAz2VCc.RW5M1ckMchJgs5F3DjISJ7uo6j.kb0W', 1, '2026-07-04 16:46:26');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_sessions`
--

CREATE TABLE `teacher_sessions` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_sessions`
--

INSERT INTO `teacher_sessions` (`id`, `teacher_id`, `session_token`, `expires_at`, `created_at`) VALUES
(1, 1, 'e8fd7e9d0472afeb0228de31d5c9dfc2b697116120ffa3be7d56981f9464d469', '2026-07-05 02:47:25', '2026-07-04 16:47:25'),
(2, 1, '2718252a48e0315d61eb0de28882649c592ec250459ce787633607d1bb31b340', '2026-07-05 05:27:34', '2026-07-04 19:27:34'),
(3, 1, 'a051b3c59f39613b6562808da8f371080e14fdb6f8048227cbeec1eff4001c3f', '2026-07-05 08:45:32', '2026-07-04 22:45:32'),
(4, 1, '650d1b32102b72945587c1cf668d8a6860c602e58df70b231e9d1d63caab1fa5', '2026-07-05 21:07:23', '2026-07-05 11:07:23'),
(5, 1, 'f3e2d07103d5608fb9ce77a4b175bfa92a6cce936f2f1ea2615f4c6b16639d0b', '2026-07-05 23:27:01', '2026-07-05 13:27:01'),
(6, 1, 'd90c2dd32b19b3ee6ceaebfef7921cd00139c0030771e3c62a2f6d3038eac859', '2026-07-06 02:05:17', '2026-07-05 16:05:17'),
(7, 1, 'c54a538025676f1f2a4c99646c86c9fbc7463736a121f412e4fbc4c0081aa34a', '2026-07-06 22:11:29', '2026-07-06 12:11:29'),
(8, 1, '35ef3f1d652ff2770ec3d583f66ef732db6ca8deb79f8d0772813b3e7bb6d0b7', '2026-07-07 22:14:58', '2026-07-07 12:14:58'),
(9, 1, '244b8feab075c772022b932ab01ed33523aaf927d78de99a1d65ce279ffa0f68', '2026-07-08 05:22:53', '2026-07-07 19:22:53'),
(10, 1, '9974285469aa64a2e477278def1884384a5ce6dc2a9ecc1c8875729d97b36b45', '2026-07-08 18:24:38', '2026-07-08 08:24:38'),
(11, 1, 'de2da3e602c1d583e0bf23937eed58a9abc9ebdb8b0bed6c4b7911e615fae97b', '2026-07-08 20:30:19', '2026-07-08 10:30:19'),
(12, 1, 'e08f0057bfd253828846b64604134136bcba2259fc2af1acf75dc3be34e79d6f', '2026-07-09 05:17:29', '2026-07-08 19:17:29'),
(13, 1, '58e57db3c6267d8a70a61f0195c73f2011864bc0b2dc6ec0b6682684bd8c8fdc', '2026-07-14 01:53:33', '2026-07-13 15:53:33'),
(19, 1, 'dc41d450b00cac2297a0feb46d22c5d80a665626fd9061dc5719f0c8cc4a9442', '2026-07-15 07:30:01', '2026-07-14 21:30:01'),
(20, 1, '8381439a09251a9ec45a3eb41e63700b418265adf2ca160ae75a5b0d187e3950', '2026-07-15 08:45:44', '2026-07-14 22:45:44'),
(21, 1, '561aa07c65d3a78549ac43c50a3b017378ac5c38455721758e286bcbfa0ab8c9', '2026-07-19 23:25:24', '2026-07-19 13:25:24'),
(22, 1, 'deabee5be13065ded1803bce07a55bf550569fdedbbd870785cd1e53b76263df', '2026-07-21 07:20:33', '2026-07-20 21:20:33'),
(23, 1, '0458d2922c38e9c6562987e829c35f10e90a88aa6a3c9d4b89aa98d5daa15242', '2026-07-22 08:45:51', '2026-07-21 22:45:51'),
(24, 1, '16dea596b6e67d2917af104426d745c01dae83371d42d5b9d749a6c21bc06947', '2026-07-22 22:46:44', '2026-07-22 12:46:44'),
(25, 1, '755871d607fc33be97fcf0ffef3ee2ed1ee3e55b48200ca4a5f9e9993edb85a3', '2026-07-22 22:47:11', '2026-07-22 12:47:11'),
(26, 1, 'a592529500b207179143dd4bf8d656a1f535e4ce8ce096272f7cdebcdeeb41dd', '2026-07-22 22:55:58', '2026-07-22 12:55:58'),
(27, 1, 'edcb913aa38f1a80cd27f72d6ce6261efd9199f29831e96fcddee052e49cacc1', '2026-07-23 06:45:05', '2026-07-22 20:45:05'),
(28, 1, '8bb29c3281a503ad93a00914f71dd7575b72477b33fcd658f0c9064239e56345', '2026-07-24 02:00:20', '2026-07-23 16:00:20'),
(29, 1, 'c0261ef51759e8a9b09b19485046476f7b6a7a87a0dc18150a2f266bc245eedc', '2026-07-25 05:43:19', '2026-07-24 19:43:19');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `subject` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `terms`
--

CREATE TABLE `terms` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `term_name` varchar(50) NOT NULL,
  `term_number` tinyint(4) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `status` enum('upcoming','active','completed','ended') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `terms`
--

INSERT INTO `terms` (`id`, `school_id`, `year`, `term_name`, `term_number`, `start_date`, `end_date`, `is_active`, `status`, `created_at`, `updated_at`) VALUES
(2, 1, 2026, 'Term 2', 2, '2026-04-27', '2026-07-31', 1, 'active', '2026-07-23 20:28:56', '2026-07-23 20:28:59'),
(3, 1, 2026, 'Term 1', 1, '2026-01-05', '2026-04-03', 0, 'upcoming', '2026-07-23 20:59:02', '2026-07-23 20:59:02');

-- --------------------------------------------------------

--
-- Table structure for table `timetables`
--

CREATE TABLE `timetables` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `year` int(11) NOT NULL DEFAULT year(curdate()),
  `name` varchar(255) NOT NULL,
  `timetable_type` enum('weekly','daily','exam') DEFAULT 'weekly',
  `term` varchar(50) NOT NULL,
  `class_id` int(11) NOT NULL,
  `status` enum('draft','active','archived') DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetables`
--

INSERT INTO `timetables` (`id`, `school_id`, `year`, `name`, `timetable_type`, `term`, `class_id`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(3, 1, 2026, 'TEST', 'weekly', 'Term 1', 1, 'draft', 1, '2026-07-23 18:03:10', '2026-07-23 18:03:10');

-- --------------------------------------------------------

--
-- Table structure for table `timetable_assignments`
--

CREATE TABLE `timetable_assignments` (
  `id` int(11) NOT NULL,
  `timetable_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `slot_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `stream_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable_assignments`
--

INSERT INTO `timetable_assignments` (`id`, `timetable_id`, `school_id`, `slot_id`, `class_id`, `stream_id`, `subject_id`, `teacher_id`, `notes`, `created_at`, `updated_at`) VALUES
(2, 3, 1, 12, 1, 1, 2, 1, 'Test', '2026-07-23 18:03:47', '2026-07-23 18:03:47');

-- --------------------------------------------------------

--
-- Table structure for table `timetable_slots`
--

CREATE TABLE `timetable_slots` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `year` int(11) NOT NULL DEFAULT year(curdate()),
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `break_type` enum('none','short_break','lunch_break','recess') DEFAULT 'none',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable_slots`
--

INSERT INTO `timetable_slots` (`id`, `school_id`, `year`, `day_of_week`, `start_time`, `end_time`, `break_type`, `created_at`) VALUES
(12, 1, 2026, 'Monday', '08:00:00', '09:59:00', 'none', '2026-07-23 18:02:23'),
(13, 1, 2026, 'Tuesday', '08:00:00', '09:59:00', 'none', '2026-07-23 18:02:23'),
(14, 1, 2026, 'Wednesday', '08:00:00', '09:59:00', 'none', '2026-07-23 18:02:23'),
(15, 1, 2026, 'Thursday', '08:00:00', '09:59:00', 'none', '2026-07-23 18:02:23'),
(16, 1, 2026, 'Friday', '08:00:00', '09:59:00', 'none', '2026-07-23 18:02:23');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `ID` int(11) NOT NULL,
  `MerchantRequestID` varchar(500) NOT NULL,
  `CheckoutRequestID` varchar(500) NOT NULL,
  `ResultCode` varchar(500) NOT NULL,
  `ResultDesc` varchar(500) NOT NULL,
  `Amount` int(11) NOT NULL,
  `MpesaReceiptNumber` varchar(500) NOT NULL,
  `PhoneNumber` varchar(500) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`ID`, `MerchantRequestID`, `CheckoutRequestID`, `ResultCode`, `ResultDesc`, `Amount`, `MpesaReceiptNumber`, `PhoneNumber`, `user_id`, `created_at`) VALUES
(1, 'TEST-1777658383-1', 'ws_CO_1777658383_123456', '0', '', 100, 'TEST123456', '254745959757', 1, '2026-05-01 17:59:43'),
(2, 'TEST-1777658408-1', 'ws_CO_1777658408_123456', '0', '', 100, 'TEST123456', '254745959757', 1, '2026-05-01 18:00:08'),
(3, 'TEST-1777658523-1', 'ws_CO_1777658523_123456', '0', '0', 100, 'TEST123456', '254745959757', 1, '2026-05-01 18:02:03'),
(4, 'TEST-1777658693-1', 'ws_CO_1777658693_123456', '0', '0', 100, 'TEST123456', '254745959757', 1, '2026-05-01 18:04:53'),
(5, 'TEST-1777658712-1', 'ws_CO_1777658712_123456', '0', '0', 100, 'TEST123456', '254745959757', 1, '2026-05-01 18:05:12'),
(6, 'TEST-1777658863-1', 'ws_CO_1777658863_123456', '0', '0', 100, 'TEST123456', '254745959757', 1, '2026-05-01 18:07:43'),
(7, 'TEST-1777659188-1', 'ws_CO_1777659188_123456', '0', '0', 100, 'TEST123456', '254745959757', 1, '2026-05-01 18:13:08'),
(8, 'TEST-1777659216-1', 'ws_CO_1777659216_123456', '0', '', 100, 'TEST123456', '254745959757', 1, '2026-05-01 18:13:36'),
(9, 'TEST-1777659885-1', 'ws_CO_1777659885_123456', '0', 'The service request is processed successfully.', 100, 'TEST123456', '', 0, '2026-05-01 18:24:45'),
(10, '', '', '', '', 0, '', '', 0, '2026-05-01 18:25:04'),
(11, 'TEST-1777659993-1', 'ws_CO_1777659993_123456', '0', 'The service request is processed successfully.', 100, 'TEST123456', '254745959757', 0, '2026-05-01 18:26:34'),
(12, 'TEST-1777663831-1', 'ws_CO_1777663831_123456', '0', '', 100, 'TEST123456', '254745959757', 1, '2026-05-01 19:30:31'),
(13, '0a50-4428-b16b-465476ed6a291685071', 'ws_CO_01052026230528387745959757', '1032', '', 0, '', '', 0, '2026-05-01 20:05:38'),
(14, 'cee7-4b64-a6de-f4bf1764ccb417241931', 'ws_CO_01052026230822471745959757', '1032', '', 0, '', '', 0, '2026-05-01 20:08:35'),
(15, '8c71-4d1f-8309-ae8c5aaab9a31596181', 'ws_CO_01052026231052605745959757', '1032', '', 0, '', '', 0, '2026-05-01 20:11:14'),
(16, 'bd6b-451f-b915-8c19b1d1509821074931', 'ws_CO_01052026231130877745959757', '1032', '', 0, '', '', 0, '2026-05-01 20:11:48'),
(17, '5285-4d3f-a554-5b0d8799646a1742765', 'ws_CO_01052026231424874745959757', '1032', '', 0, '', '', 0, '2026-05-01 20:14:35'),
(18, 'f77f-4a79-b6ef-daa9a3920cf91642906', 'ws_CO_01052026231710292745959757', '1032', '', 0, '', '', 0, '2026-05-01 20:17:21'),
(19, '24aa-4bbc-a18a-a61cf906c76a24991932', 'ws_CO_01052026231755700745959757', '1032', '', 0, '', '', 0, '2026-05-01 20:18:04'),
(20, '243c-4ff4-b2ee-b484f2a4631943622137', 'ws_CO_01052026231845390745959757', '1032', '', 0, '', '', 0, '2026-05-01 20:19:03'),
(21, '20c1-4224-8c15-3273867dd8d21756430', 'ws_CO_01052026232114391745959757', '1032', '', 0, '', '', 0, '2026-05-01 20:21:23'),
(22, '8c71-4d1f-8309-ae8c5aaab9a31608902', 'ws_CO_01052026232302819745959757', '1032', '', 0, '', '', 0, '2026-05-01 20:23:11'),
(23, 'b1a4-4079-8da7-0fc363f70a63205928', 'ws_CO_01052026232516429745959757', '1032', '', 0, '', '', 0, '2026-05-01 20:25:24'),
(24, 'b1a4-4079-8da7-0fc363f70a63273644', 'ws_CO_02052026005724973745959757', '1037', '', 0, '', '', 0, '2026-05-01 21:57:54'),
(25, '8a3d-47ce-b5d3-f697c62c367e1776604', 'ws_CO_02052026010037008745959757', '1032', '', 0, '', '', 0, '2026-05-01 22:00:46'),
(26, '8a3d-47ce-b5d3-f697c62c367e1776807', 'ws_CO_02052026010100422745959757', '1037', '', 0, '', '', 0, '2026-05-01 22:01:29'),
(27, 'cb50-4a8c-a8fa-5fcae33e5e6b14195686', 'ws_CO_02052026010220137745959757', '1032', '', 0, '', '', 0, '2026-05-01 22:02:28'),
(28, 'cee7-4b64-a6de-f4bf1764ccb417329861', 'ws_CO_02052026010241324745959757', '1032', '', 0, '', '', 0, '2026-05-01 22:02:50'),
(29, '5285-4d3f-a554-5b0d8799646a1824502', 'ws_CO_02052026010352999745959757', '1032', '', 0, '', '', 0, '2026-05-01 22:04:01'),
(30, 'faff-4353-91c5-e4b515a7a8aa24816659', 'ws_CO_02052026010424605745959757', '1032', '', 0, '', '', 0, '2026-05-01 22:04:31'),
(31, '734b-4e63-81e2-3144edc318ba1781887', 'ws_CO_02052026010532043745959757', '1032', '', 0, '', '', 0, '2026-05-01 22:05:40'),
(32, 'c5f0-4aae-a8b4-53e8526d181a211356', 'ws_CO_02052026010656527745959757', '1032', '', 0, '', '', 0, '2026-05-01 22:07:04'),
(33, 'd961-43c1-80e0-c5f9e3ceaff121164333', 'ws_CO_02052026011050161745959757', '1032', '', 0, '', '', 0, '2026-05-01 22:11:08'),
(34, '580d-4a6d-9276-06579ec0b75e1736856', 'ws_CO_02052026011349344745959757', '1032', '', 0, '', '', 0, '2026-05-01 22:13:58'),
(35, '4ddd-4866-824e-3f944c695ba345874857', 'ws_CO_02052026011420783745959757', '1032', '', 0, '', '', 0, '2026-05-01 22:14:33'),
(36, 'b18e-4425-8db3-935909f7a54d46259245', 'ws_CO_02052026011504972745959757', '0', '', 1, 'UE2N82WFAK', '254745959757', 1, '2026-05-01 22:15:15'),
(37, 'b1a4-4079-8da7-0fc363f70a63284358', 'ws_CO_02052026012035616745959757', '1032', '', 0, '', '', 0, '2026-05-01 22:20:48'),
(38, 'f43e-4304-89a1-994d43718d5425828419', 'ws_CO_02052026012155025745959757', '1032', '', 0, '', '', 0, '2026-05-01 22:22:04'),
(39, 'bd6b-451f-b915-8c19b1d1509821167754', 'ws_CO_02052026012221792745959757', '0', '', 1, 'UE2N82WAJL', '254745959757', 1, '2026-05-01 22:22:31'),
(40, 'b18e-4425-8db3-935909f7a54d46263775', 'ws_CO_02052026012537864745959757', '0', '', 1, 'UE2N82W8ZN', '254745959757', 1, '2026-05-01 22:25:48'),
(41, '3ac6-4536-95db-4482d9bbe1bc24844579', 'ws_CO_02052026012756440745959757', '0', '', 1, 'UE2N82WGV1', '254745959757', 1, '2026-05-01 22:28:08'),
(42, '580d-4a6d-9276-06579ec0b75e1752028', 'ws_CO_02052026015011412745959757', '1032', '', 0, '', '', 0, '2026-05-01 22:50:20'),
(43, '5285-4d3f-a554-5b0d8799646a1844274', 'ws_CO_02052026015039539745959757', '0', '', 1, 'UE2N82WBRO', '254745959757', 1, '2026-05-01 22:50:52'),
(44, 'f12b-4928-9fb7-40b7c9e9571a24895528', 'ws_CO_02052026015126317745959757', '17', '', 0, '', '', 0, '2026-05-01 22:51:38'),
(45, '5285-4d3f-a554-5b0d8799646a1845327', 'ws_CO_02052026015323984745959757', '1032', '', 0, '', '', 0, '2026-05-01 22:53:32'),
(46, '8a3d-47ce-b5d3-f697c62c367e2664612', 'ws_CO_02052026163548712745959757', '1032', '', 0, '', '', 0, '2026-05-02 13:36:00'),
(47, '5285-4d3f-a554-5b0d8799646a2713083', 'ws_CO_02052026163735983745959757', '1032', '', 0, '', '', 0, '2026-05-02 13:37:43'),
(48, '734b-4e63-81e2-3144edc318ba2671506', 'ws_CO_02052026163812232745959757', '1032', '', 0, '', '', 0, '2026-05-02 13:38:21'),
(49, '47bf-4499-a797-c1bfe826e3fe4118907', 'ws_CO_02052026165253981745959757', '1032', '', 0, '', '', 0, '2026-05-02 13:53:06'),
(50, 'e356-46bc-981d-c7eb48e9e49c22127815', 'ws_CO_02052026165517820745959757', '1032', '', 0, '', '', 0, '2026-05-02 13:55:24'),
(51, '8c71-4d1f-8309-ae8c5aaab9a32607650', 'ws_CO_02052026165756210745959757', '1032', '', 0, '', '', 0, '2026-05-02 13:58:07'),
(52, 'cf39-4b49-9214-58520a73e0552699609', 'ws_CO_02052026170006595745959757', '1032', '', 0, '', '', 0, '2026-05-02 14:00:14'),
(53, '5285-4d3f-a554-5b0d8799646a2752621', 'ws_CO_02052026170026499745959757', '1032', '', 0, '', '', 0, '2026-05-02 14:00:37'),
(54, 'cf39-4b49-9214-58520a73e0552703530', 'ws_CO_02052026170226266745959757', '1032', '', 0, '', '', 0, '2026-05-02 14:02:38'),
(55, '734b-4e63-81e2-3144edc318ba2715959', 'ws_CO_02052026170412442745959757', '1032', '', 0, '', '', 0, '2026-05-02 14:04:26'),
(56, '5285-4d3f-a554-5b0d8799646a2759684', 'ws_CO_02052026170442670745959757', '1032', '', 0, '', '', 0, '2026-05-02 14:04:53'),
(57, '6eb1-4876-a7ef-09767c5ebe1a2770855', 'ws_CO_02052026170524507745959757', '0', '', 1, 'UE2N82YOV2', '254745959757', 1, '2026-05-02 14:05:36'),
(58, 'b424-4bec-973f-fac88b9b7d1925339935', 'ws_CO_02052026171352918745959757', '1032', '', 0, '', '', 0, '2026-05-02 14:14:00'),
(59, 'd961-43c1-80e0-c5f9e3ceaff122109287', 'ws_CO_02052026171604312745959757', '1032', '', 0, '', '', 0, '2026-05-02 14:16:12'),
(60, '3ac6-4536-95db-4482d9bbe1bc25784377', 'ws_CO_02052026171619424745959757', '0', '', 1, 'UE2N82YQBV', '254745959757', 1, '2026-05-02 14:16:30'),
(61, '47bf-4499-a797-c1bfe826e3fe4171430', 'ws_CO_02052026172413482745959757', '1032', '', 0, '', '', 0, '2026-05-02 14:24:21'),
(62, '243c-4ff4-b2ee-b484f2a4631944661092', 'ws_CO_02052026172434504745959757', '1032', '', 0, '', '', 0, '2026-05-02 14:24:42'),
(63, '243c-4ff4-b2ee-b484f2a4631944661827', 'ws_CO_02052026172501276745959757', '1032', '', 0, '', '', 0, '2026-05-02 14:25:11'),
(64, 'b424-4bec-973f-fac88b9b7d1925359827', 'ws_CO_02052026172606713745959757', '1032', '', 0, '', '', 0, '2026-05-02 14:26:17'),
(65, 'test-merchant-1777732095', 'test-checkout-1777732095', '0', '', 100, 'TEST1777732095', '254745959757', 1, '2026-05-02 14:28:15'),
(66, 'test-merchant-1777732113', 'test-checkout-1777732113', '0', '', 100, 'TEST1777732113', '254745959757', 1, '2026-05-02 14:28:33'),
(67, 'test-merchant-1777732209', 'test-checkout-1777732209', '0', '', 100, 'TEST1777732209', '254745959757', 1, '2026-05-02 14:30:09'),
(68, 'test-merchant-1777732254', 'test-checkout-1777732254', '0', '', 100, 'TEST1777732254', '254745959757', 1, '2026-05-02 14:30:54'),
(69, '', '', '0', '', 100, 'TEST1777732254', '254745959757', 1, '2026-05-02 14:30:54'),
(70, 'cb50-4a8c-a8fa-5fcae33e5e6b15169605', 'ws_CO_02052026173144008745959757', '1032', '', 0, '', '', 0, '2026-05-02 14:31:54'),
(71, 'e356-46bc-981d-c7eb48e9e49c22190419', 'ws_CO_02052026173312426745959757', '17', '', 0, '', '', 0, '2026-05-02 14:33:22'),
(72, '20c1-4224-8c15-3273867dd8d22826009', 'ws_CO_02052026174002991745959757', '1032', '', 0, '', '', 0, '2026-05-02 14:40:11'),
(73, 'cf39-4b49-9214-58520a73e0552767345', 'ws_CO_02052026174054899745959757', '1032', '', 0, '', '', 0, '2026-05-02 14:41:03'),
(74, 'c5f0-4aae-a8b4-53e8526d181a1206053', 'ws_CO_02052026174122058745959757', '1032', '', 0, '', '', 0, '2026-05-02 14:41:30'),
(75, 'cb50-4a8c-a8fa-5fcae33e5e6b15186297', 'ws_CO_02052026174137678745959757', '0', '', 1, 'UE2N82YROX', '254745959757', 1, '2026-05-02 14:41:50'),
(76, '', '', '0', '', 1, 'UE2N82YROX', '254745959757', 1, '2026-05-02 14:41:50'),
(77, '243c-4ff4-b2ee-b484f2a4631944787347', 'ws_CO_02052026183709911745959757', '1032', '', 0, '', '', 0, '2026-05-02 15:37:17'),
(78, 'c5f0-4aae-a8b4-53e8526d181a1305265', 'ws_CO_02052026183740282745959757', '1032', '', 0, '', '', 0, '2026-05-02 15:37:51'),
(79, '518d-4d20-a46f-b3b1ed75c58e74067', 'ws_CO_02052026201847230745959757', '1032', '', 0, '', '', 0, '2026-05-02 17:18:57'),
(80, 'bd6b-451f-b915-8c19b1d1509822528786', 'ws_CO_02052026202745294745959757', '1032', '', 0, '', '', 0, '2026-05-02 17:27:59'),
(81, '317e-49ad-820f-d975861a3c49179296', 'ws_CO_02052026202812156745959757', '1032', '', 0, '', '', 0, '2026-05-02 17:28:20'),
(82, '003b-44b6-9756-96f607929e89185134', 'ws_CO_02052026202832686745959757', '1032', '', 0, '', '', 0, '2026-05-02 17:28:41'),
(83, 'f43e-4304-89a1-994d43718d5427194638', 'ws_CO_02052026202910438745959757', '1032', '', 0, '', '', 0, '2026-05-02 17:29:19'),
(84, 'ae66-41da-affc-3da2d24786b7118444', 'ws_CO_02052026203144708745959757', '1032', '', 0, '', '', 0, '2026-05-02 17:31:57'),
(85, 'ab98-48b8-b94b-de0f3806796a23030704', 'ws_CO_02052026224612361745959757', '1037', '', 0, '', '', 0, '2026-05-02 19:46:32'),
(86, '24aa-4bbc-a18a-a61cf906c76a26747670', 'ws_CO_02052026232406429745959757', '1032', '', 0, '', '', 0, '2026-05-02 20:24:15'),
(87, '317e-49ad-820f-d975861a3c49430411', 'ws_CO_02052026232426967745959757', '1032', '', 0, '', '', 0, '2026-05-02 20:24:35'),
(88, 'cf39-4b49-9214-58520a73e0553355061', 'ws_CO_02052026232520504745959757', '1032', '', 0, '', '', 0, '2026-05-02 20:25:30'),
(89, 'f12b-4928-9fb7-40b7c9e9571a26610996', 'ws_CO_03052026001920438745959757', '1032', '', 0, '', '', 0, '2026-05-02 21:19:30'),
(90, '5722-4698-b975-3eba550494af228545', 'ws_CO_03052026002502060745959757', '1032', '', 0, '', '', 0, '2026-05-02 21:25:11'),
(91, '4ddd-4866-824e-3f944c695ba347612792', 'ws_CO_03052026002609640745959757', '1032', '', 0, '', '', 0, '2026-05-02 21:26:18'),
(92, 'bac5-4ff5-9cb0-c8aea74f122f578070', 'ws_CO_03052026004230817745959757', '1032', '', 0, '', '', 0, '2026-05-02 21:42:49'),
(93, '280c-4dcb-8547-a619318dd7c3444479', 'ws_CO_03052026005013294745959757', '1032', '', 0, '', '', 0, '2026-05-02 21:50:25'),
(94, 'ab98-48b8-b94b-de0f3806796a23161586', 'ws_CO_03052026005258460745959757', '1032', '', 0, '', '', 0, '2026-05-02 21:53:07'),
(95, 'ae66-41da-affc-3da2d24786b7499463', 'ws_CO_03052026010014571745959757', '1032', '', 0, '', '', 0, '2026-05-02 22:00:23'),
(96, 'cf39-4b49-9214-58520a73e0553446625', 'ws_CO_03052026010721044745959757', '1032', '', 0, '', '', 0, '2026-05-02 22:07:30'),
(97, '317e-4713-9385-903febbe714b673951', 'ws_CO_03052026010739366745959757', '1032', '', 0, '', '', 0, '2026-05-02 22:07:47'),
(98, '5722-4698-b975-3eba550494af260192', 'ws_CO_03052026010838700745959757', '1032', '', 0, '', '', 0, '2026-05-02 22:08:46'),
(99, 'faff-4353-91c5-e4b515a7a8aa26587859', 'ws_CO_03052026010856983745959757', '0', '', 1, 'UE3N830R5A', '254745959757', 1, '2026-05-02 22:09:09'),
(100, '', '', '0', '', 1, 'UE3N830R5A', '254745959757', 1, '2026-05-02 22:09:09'),
(101, '5722-4698-b975-3eba550494af665116', 'ws_CO_03052026105704542745959757', '1032', '', 0, '', '', 0, '2026-05-03 07:57:11'),
(102, 'e0e7-43a0-abb7-83017878444d724680', 'ws_CO_03052026110027647745959757', '1037', '', 0, '', '', 0, '2026-05-03 08:00:56'),
(103, 'e575-44f8-9f48-cb08c3280bb1849021', 'ws_CO_03052026110356112745959757', '1032', '', 0, '', '', 0, '2026-05-03 08:04:04'),
(104, 'f43e-4304-89a1-994d43718d5428038703', 'ws_CO_03052026113105485745959757', '1032', '', 0, '', '', 0, '2026-05-03 08:31:12'),
(105, '5722-4698-b975-3eba550494af737295', 'ws_CO_03052026115246528745959757', '1032', '', 0, '', '', 0, '2026-05-03 08:52:54'),
(106, 'f43e-4304-89a1-994d43718d5428069921', 'ws_CO_03052026115522944745959757', '1032', '', 0, '', '', 0, '2026-05-03 08:55:31'),
(107, '19ef-4913-8503-68662e7d91271004360', 'ws_CO_03052026115604373745959757', '1032', '', 0, '', '', 0, '2026-05-03 08:56:13'),
(108, '518d-4d20-a46f-b3b1ed75c58e995282', 'ws_CO_03052026115851167745959757', '1032', '', 0, '', '', 0, '2026-05-03 08:58:58'),
(109, '21f1-425a-9052-a4369fa992b614827', 'ws_CO_03052026172355516745959757', '1032', '', 0, '', '', 0, '2026-05-03 14:24:02'),
(110, 'e575-44f8-9f48-cb08c3280bb11412935', 'ws_CO_03052026172538792745959757', '1032', '', 0, '', '', 0, '2026-05-03 14:25:44'),
(111, 'ab98-48b8-b94b-de0f3806796a24107739', 'ws_CO_03052026172630910745959757', '1032', '', 0, '', '', 0, '2026-05-03 14:26:37'),
(112, 'b424-4bec-973f-fac88b9b7d1927101687', 'ws_CO_03052026172703112745959757', '1032', '', 0, '', '', 0, '2026-05-03 14:27:09'),
(113, '1985-496c-8299-e2b307ef252b3821793', 'ws_CO_04052026094615261745959757', '1032', '', 0, '', '', 0, '2026-05-04 06:46:27'),
(114, '84cb-4c6e-91a0-4fba293e705363143', 'ws_CO_06072026180355817745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-06 15:04:02'),
(115, '84cb-4c6e-91a0-4fba293e705363336', 'ws_CO_06072026180700777745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-06 15:07:06'),
(116, '5f26-4b5c-be56-a74e7c15db1920995', 'ws_CO_06072026181305975745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-06 15:13:12'),
(117, '934e-485f-93a1-766dc71b6c9a98448', 'ws_CO_06072026181349475745959757', '0', 'The service request is processed successfully.', 1, 'UG6N8AI79R', '254745959757', 0, '2026-07-06 15:14:11'),
(118, '84cb-4c6e-91a0-4fba293e705364153', 'ws_CO_06072026181938961745959757', '0', 'The service request is processed successfully.', 1, 'UG6N8AIAGX', '254745959757', 0, '2026-07-06 15:19:54'),
(119, '84cb-4c6e-91a0-4fba293e705364589', 'ws_CO_06072026182650228745959757', '0', 'The service request is processed successfully.', 1, 'UG6N8AIEFT', '254745959757', 0, '2026-07-06 15:26:59'),
(120, '84cb-4c6e-91a0-4fba293e705365182', 'ws_CO_06072026183827234745959757', '0', 'The service request is processed successfully.', 1, 'UG6N8AIHB4', '254745959757', 0, '2026-07-06 15:38:37'),
(121, '934e-485f-93a1-766dc71b6c9a98973', 'ws_CO_06072026183900810745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-06 15:39:06'),
(122, '934e-485f-93a1-766dc71b6c9a98986', 'ws_CO_06072026183934251745959757', '0', 'The service request is processed successfully.', 1, 'UG6N8AIET0', '254745959757', 0, '2026-07-06 15:39:45'),
(123, 'd93a-4656-96f7-cb427c2ad97d17082', 'ws_CO_07072026134424906745959757', '1037', 'No response from user.', 0, '', '', 0, '2026-07-07 10:44:51'),
(124, '6766-4f14-b588-802e2e59785d5060', 'ws_CO_07072026134526558745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-07 10:45:36'),
(125, '58c3-464f-b59d-de06f0e595a942448', 'ws_CO_07072026134642480745959757', '0', 'The service request is processed successfully.', 1, 'UG7N8ALA2Y', '254745959757', 0, '2026-07-07 10:46:51'),
(126, '6766-4f14-b588-802e2e59785d26256', 'ws_CO_07072026234056220745959757', '0', 'The service request is processed successfully.', 1, 'UG7N8ANIWC', '254745959757', 0, '2026-07-07 20:41:15'),
(127, 'test-merchant-1783460732', 'ws_CO_TEST_1783460732', '0', 'The service request is processed successfully.', 1, 'TEST1783460732', '254700000000', 0, '2026-07-07 21:45:32'),
(128, '58c3-464f-b59d-de06f0e595a981710', 'ws_CO_08072026004628902745959757', '0', 'The service request is processed successfully.', 1, 'UG8N8ANLTG', '254745959757', 0, '2026-07-07 21:46:40'),
(129, '6766-4f14-b588-802e2e59785d53732', 'ws_CO_08072026113856483745959757', '0', 'The service request is processed successfully.', 1, 'UG8N8AOV3O', '254745959757', 0, '2026-07-08 08:39:10'),
(130, 'ff3e-4fa4-abc0-8eb3aa92c0d9111380', 'ws_CO_15072026011127371745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-14 22:11:34'),
(131, '3a62-4214-aa55-1c05a6d85a1c29560', 'ws_CO_15072026011155751745959757', '0', 'The service request is processed successfully.', 1, 'UGFN8BG3G5', '254745959757', 0, '2026-07-14 22:12:04'),
(132, '3562-4784-aa83-0330f61ef368132943', 'ws_CO_16072026121807872745959757', '0', 'The service request is processed successfully.', 1, 'UGGN8BL5Y8', '254745959757', 0, '2026-07-16 09:18:16'),
(133, '3562-4784-aa83-0330f61ef368132985', 'ws_CO_16072026121906511745959757', '0', 'The service request is processed successfully.', 2, 'UGGN8BL4JP', '254745959757', 0, '2026-07-16 09:19:13'),
(134, '6839-428d-8589-2bcb00296f3742632', 'ws_CO_19072026161247034745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-19 13:13:03'),
(135, '7cd0-4a28-8d04-cb27fe4732c642249', 'ws_CO_19072026162300421745959757', '0', 'The service request is processed successfully.', 1, 'UGJN801DNO', '254745959757', 0, '2026-07-19 13:23:09'),
(136, '6839-428d-8589-2bcb00296f3743210', 'ws_CO_19072026162337917745959757', '0', 'The service request is processed successfully.', 2, 'UGJN801C80', '254745959757', 0, '2026-07-19 13:23:46'),
(137, '6839-428d-8589-2bcb00296f3756241', 'ws_CO_19072026200320607745959757', '0', 'The service request is processed successfully.', 2, 'UGJN802DVF', '254745959757', 0, '2026-07-19 17:03:38'),
(138, 'test123', 'test456', '0', 'Test callback', 0, '', '', 0, '2026-07-19 19:33:07'),
(139, '6839-428d-8589-2bcb00296f3768705', 'ws_CO_19072026231905970745959757', '0', 'The service request is processed successfully.', 1, 'UGJN8032CK', '254745959757', 0, '2026-07-19 20:19:18'),
(140, '6839-428d-8589-2bcb00296f3788764', 'ws_CO_20072026051830140745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-20 02:18:37'),
(141, 'b824-4eab-b51c-485633e35ec2125354', 'ws_CO_22072026191848584745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 16:18:55'),
(142, 'b824-4eab-b51c-485633e35ec2125392', 'ws_CO_22072026191945220745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 16:20:04'),
(143, 'b824-4eab-b51c-485633e35ec2125492', 'ws_CO_22072026192258490745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 16:23:04'),
(144, 'f158-452a-801f-e6b00aad1141126480', 'ws_CO_22072026192907207745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 16:29:12'),
(145, 'b824-4eab-b51c-485633e35ec2125844', 'ws_CO_22072026192952656745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 16:30:12'),
(146, 'f158-452a-801f-e6b00aad1141126676', 'ws_CO_22072026193128295745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 16:31:32'),
(147, 'b824-4eab-b51c-485633e35ec2126014', 'ws_CO_22072026193405309745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 16:34:29'),
(148, 'f158-452a-801f-e6b00aad1141127195', 'ws_CO_22072026193812993745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 16:38:18'),
(149, 'b824-4eab-b51c-485633e35ec2126266', 'ws_CO_22072026194007769745959757', '0', 'The service request is processed successfully.', 1, 'UGMN80E49M', '254745959757', 0, '2026-07-22 16:40:16'),
(150, 'b824-4eab-b51c-485633e35ec2126329', 'ws_CO_22072026194127356745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 16:41:32'),
(151, 'f158-452a-801f-e6b00aad1141127378', 'ws_CO_22072026194156136745959757', '0', 'The service request is processed successfully.', 1, 'UGMN80DZXL', '254745959757', 0, '2026-07-22 16:42:04'),
(152, 'b824-4eab-b51c-485633e35ec2126463', 'ws_CO_22072026194449556745959757', '2001', 'The initiator information is invalid.', 0, '', '', 0, '2026-07-22 16:44:56'),
(153, 'b824-4eab-b51c-485633e35ec2126788', 'ws_CO_22072026195116832745959757', '0', 'The service request is processed successfully.', 1, 'UGMN80E4ND', '254745959757', 0, '2026-07-22 16:51:25'),
(154, 'f158-452a-801f-e6b00aad1141128384', 'ws_CO_22072026195544088745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 16:55:49'),
(155, 'b824-4eab-b51c-485633e35ec2127035', 'ws_CO_22072026195949754745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 16:59:56'),
(156, 'f158-452a-801f-e6b00aad1141139419', 'ws_CO_22072026215340711745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 18:53:45'),
(157, 'b824-4eab-b51c-485633e35ec2132757', 'ws_CO_22072026220341003745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 19:03:49'),
(158, 'f158-452a-801f-e6b00aad1141140683', 'ws_CO_22072026220550240745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 19:05:55'),
(159, 'f158-452a-801f-e6b00aad1141141488', 'ws_CO_22072026221338537745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 19:13:43'),
(160, 'f158-452a-801f-e6b00aad1141141558', 'ws_CO_22072026221423324745959757', '0', 'The service request is processed successfully.', 1, 'UGMN80ELXU', '254745959757', 0, '2026-07-22 19:14:35'),
(161, 'b824-4eab-b51c-485633e35ec2133488', 'ws_CO_22072026221634984745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 19:16:39'),
(162, 'f158-452a-801f-e6b00aad1141142118', 'ws_CO_22072026222027706745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 19:20:45'),
(163, 'b824-4eab-b51c-485633e35ec2133791', 'ws_CO_22072026222253945745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 19:23:02'),
(164, 'b824-4eab-b51c-485633e35ec2133849', 'ws_CO_22072026222353546745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 19:24:02'),
(165, 'f158-452a-801f-e6b00aad1141142608', 'ws_CO_22072026222529556745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 19:25:37'),
(166, 'b824-4eab-b51c-485633e35ec2134011', 'ws_CO_22072026222640997745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 19:26:51'),
(167, 'f158-452a-801f-e6b00aad1141143200', 'ws_CO_22072026222944291745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 19:29:49'),
(168, 'f158-452a-801f-e6b00aad1141143287', 'ws_CO_22072026223016661745959757', '0', 'The service request is processed successfully.', 1, 'UGMN80ES0M', '254745959757', 0, '2026-07-22 19:30:24'),
(169, 'b824-4eab-b51c-485633e35ec2134478', 'ws_CO_22072026223423293745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 19:34:30'),
(170, 'f158-452a-801f-e6b00aad1141143843', 'ws_CO_22072026223457757745959757', '0', 'The service request is processed successfully.', 1, 'UGMN80ES2Y', '254745959757', 0, '2026-07-22 19:35:07'),
(171, 'f158-452a-801f-e6b00aad1141144177', 'ws_CO_22072026223739757745959757', '1037', 'No response from user.', 0, '', '', 0, '2026-07-22 19:38:06'),
(172, 'b824-4eab-b51c-485633e35ec2134749', 'ws_CO_22072026223818462745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 19:38:28'),
(173, 'b824-4eab-b51c-485633e35ec2135680', 'ws_CO_22072026225413524745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 19:54:18'),
(174, 'f158-452a-801f-e6b00aad1141146234', 'ws_CO_22072026225433145745959757', '0', 'The service request is processed successfully.', 1, 'UGMN80EP8R', '254745959757', 0, '2026-07-22 19:54:42'),
(175, 'f158-452a-801f-e6b00aad1141147184', 'ws_CO_22072026230315885745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 20:03:25'),
(176, 'f158-452a-801f-e6b00aad1141153694', 'ws_CO_23072026001309411745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-22 21:13:17'),
(177, 'f158-452a-801f-e6b00aad1141251317', 'ws_CO_23072026192425141745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-23 16:24:32'),
(178, 'f158-452a-801f-e6b00aad1141251324', 'ws_CO_23072026192449642745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-23 16:24:56'),
(179, 'b824-4eab-b51c-485633e35ec2201693', 'ws_CO_23072026192534871745959757', '0', 'The service request is processed successfully.', 1, 'UGNN80HUQC', '254745959757', 0, '2026-07-23 16:25:44'),
(180, '081a-46f3-bf79-f6857fb872e710000', 'ws_CO_24072026013646597745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-23 22:36:53'),
(181, '6e47-4967-bcb9-74445bede7d227056', 'ws_CO_24072026013708206745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-23 22:37:13'),
(182, '6e47-4967-bcb9-74445bede7d227072', 'ws_CO_24072026013732159745959757', '0', 'The service request is processed successfully.', 1, 'UGON80IRKH', '254745959757', 0, '2026-07-23 22:37:42'),
(183, '081a-46f3-bf79-f6857fb872e710101', 'ws_CO_24072026014008491745959757', '0', 'The service request is processed successfully.', 1, 'UGON80IRKO', '254745959757', 0, '2026-07-23 22:40:18'),
(184, '17b4-4bbb-a2b0-7a04972eda64210', 'ws_CO_24072026162432414745959757', '1037', 'No response from user.', 0, '', '', 0, '2026-07-24 13:24:59'),
(185, '6e47-4967-bcb9-74445bede7d2104612', 'ws_CO_24072026162513694745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-24 13:25:16'),
(186, '081a-46f3-bf79-f6857fb872e753393', 'ws_CO_24072026162537306745959757', '1037', 'No response from user.', 0, '', '', 0, '2026-07-24 13:26:04'),
(187, '081a-46f3-bf79-f6857fb872e753435', 'ws_CO_24072026162654570745959757', '1037', 'No response from user.', 0, '', '', 0, '2026-07-24 13:27:22'),
(188, '081a-46f3-bf79-f6857fb872e753462', 'ws_CO_24072026162751942745959757', '0', 'The service request is processed successfully.', 1, 'UGON80L1VF', '254745959757', 0, '2026-07-24 13:28:04'),
(189, 'b13d-4e1d-8fb5-0f0d66c4323014612', 'ws_CO_25072026024826291745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-24 23:48:34'),
(190, '6e47-4967-bcb9-74445bede7d2149207', 'ws_CO_25072026024934806745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-24 23:49:41'),
(191, 'b13d-4e1d-8fb5-0f0d66c4323014685', 'ws_CO_25072026025002278745959757', '2001', 'The initiator information is invalid.', 0, '', '', 0, '2026-07-24 23:50:11'),
(192, 'b13d-4e1d-8fb5-0f0d66c4323014736', 'ws_CO_25072026025028152745959757', '0', 'The service request is processed successfully.', 1, 'UGPN80MX5K', '254745959757', 0, '2026-07-24 23:50:40'),
(193, '6e47-4967-bcb9-74445bede7d2149995', 'ws_CO_25072026030404591745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-25 00:04:10'),
(194, 'b13d-4e1d-8fb5-0f0d66c4323016711', 'ws_CO_25072026033934439745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-25 00:39:40'),
(195, 'b13d-4e1d-8fb5-0f0d66c4323016767', 'ws_CO_25072026034015366745959757', '1032', 'Request Cancelled by user.', 0, '', '', 0, '2026-07-25 00:40:21');

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
  `reset_expires_at` datetime DEFAULT NULL,
  `google_uid` varchar(255) DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `verification_code`, `code_expires_at`, `is_verified`, `reset_code`, `reset_expires_at`, `google_uid`, `photo_url`, `email_verified`) VALUES
(1, 'Brian Onyango', 'otienobrian029@gmail.com', '$2y$10$mbbgZh98BWWdD0JAGo98a.cRm7s/szNj2kBid3ceJc8Mq2Ms6a.em', 'admin', NULL, '2025-07-12 13:10:16', 1, '835278', '2026-06-28 17:55:41', NULL, NULL, 0),
(26, 'Brian Onyango', 'otisbrian46@gmail.com', '$2y$10$vfafGTj8VaRV91mdP67ACulWs8bPuke.988xpmMLeSfVN0b879tky', 'user', NULL, NULL, 1, NULL, NULL, NULL, NULL, 0),
(3, 'omar', 'omarwaraka10@gmail.com', '$2y$10$gbcQq8JsJc.Fw1x3HyPn4einwXC.NV063kKBKK4Ttsq93tK5G1EWO', 'user', NULL, NULL, 1, NULL, NULL, NULL, NULL, 0),
(5, 'stanley juma', 'stareen258@gmail.com', '$2y$10$8/7D1lCSHZF463hPmiouo.uN5q40NVg.4mb6sCnT343akQvaHeT7m', 'user', NULL, NULL, 1, NULL, NULL, NULL, NULL, 0),
(6, 'William Steve Odhiambo', 'williamsteve10699@gmail.com', '$2y$10$9ILMPtccH2Om9d7sVFacGedBVGTVUg.wKL/xChXBP9GgmQmH58vH6', 'user', NULL, NULL, 1, NULL, NULL, NULL, NULL, 0),
(7, 'Anjeline Auma', 'anjelineauma@gmail.com', '$2y$10$sBCBTsDBFIkdFrLjGw3rNeOTegcKI4KMzSWYbHAUwHplS1zJLRN7e', 'admin', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0),
(10, 'Jiven ochieng', 'Onsongojiven095@gmail.com', '$2y$10$ibVeVvzzkBfWcLsd2Pwvaef7kXBXEPSXZyuUf3ZRKRzvFXi4M4DyG', 'user', NULL, NULL, 1, NULL, NULL, NULL, NULL, 0),
(11, 'Brian Philip Ombiro ', 'ombirophilibra@gmail.com', '$2y$10$hWpvE297aEETC/YY82jkPexYDyNdLTGXRopN2Jl/4n6ex4/vtOPEO', 'user', NULL, NULL, 1, NULL, NULL, NULL, NULL, 0),
(15, 'omundo peter', 'omundopeter@6gmail.com', '$2y$10$1VAAWJTciZrryznlZXQKnuLE5l931VYSfeWkzhAOT4BlSjAk55kIy', 'user', NULL, NULL, 1, NULL, NULL, NULL, NULL, 0),
(27, 'Kenyaeduhub', 'kenyaeduhub@gmail.com', '$2y$10$XEpuf9IoF2xJA8MTeYMbV.vpL9AgO46k9Dxs793TbB6qHEXGYN5m6', 'admin', NULL, NULL, 1, NULL, NULL, NULL, NULL, 0),
(28, 'Omollo Vincent', 'vincentomollo22@gmail.com', '$2y$10$2qIDpJ5jyrJfngZoG0PGpO/DnbvivzQU5F4Rp3gSAXL7qVSn2hrYS', 'user', NULL, NULL, 1, NULL, NULL, NULL, NULL, 0),
(29, 'Japhlet', 'japhletotieno99@gmail.com', '$2y$10$.B4tqDRfP0yF9ydxP0XGoeHJyIiiRYPv60VSHTTqdKbu42sU9V9nO', 'user', NULL, NULL, 1, NULL, NULL, NULL, NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_performance`
--
ALTER TABLE `academic_performance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_term_year` (`term`,`year`),
  ADD KEY `exam_type_id` (`exam_type_id`);

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_school_year` (`school_id`,`year`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `idx_school_teacher` (`school_id`,`teacher_id`),
  ADD KEY `idx_class` (`class_id`),
  ADD KEY `idx_subject` (`subject_id`),
  ADD KEY `idx_type` (`assignment_type`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `assignment_comments`
--
ALTER TABLE `assignment_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_assignment_id` (`assignment_id`),
  ADD KEY `idx_author_id` (`author_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `assignment_downloads`
--
ALTER TABLE `assignment_downloads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_assignment` (`assignment_id`),
  ADD KEY `idx_user` (`user_type`,`user_id`),
  ADD KEY `idx_download_date` (`download_date`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`date`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_isbn` (`isbn`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_books_category` (`category`),
  ADD KEY `idx_books_status` (`status`);

--
-- Indexes for table `book_borrowings`
--
ALTER TABLE `book_borrowings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_book_id` (`book_id`),
  ADD KEY `idx_borrower_type` (`borrower_type`),
  ADD KEY `idx_borrower_id` (`borrower_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`);

--
-- Indexes for table `book_categories`
--
ALTER TABLE `book_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_category` (`school_id`,`category_name`);

--
-- Indexes for table `book_history`
--
ALTER TABLE `book_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_book_history_book_id` (`book_id`),
  ADD KEY `idx_book_history_school_id` (`school_id`);

--
-- Indexes for table `book_reservations`
--
ALTER TABLE `book_reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `idx_book_reservations_book_id` (`book_id`),
  ADD KEY `idx_book_reservations_status` (`status`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_id` (`school_id`);

--
-- Indexes for table `cleanliness_checks`
--
ALTER TABLE `cleanliness_checks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `disciplinary_action_types`
--
ALTER TABLE `disciplinary_action_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_action` (`school_id`,`action_name`);

--
-- Indexes for table `disciplinary_committee`
--
ALTER TABLE `disciplinary_committee`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_member` (`school_id`,`user_id`);

--
-- Indexes for table `disciplinary_records`
--
ALTER TABLE `disciplinary_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_incident_date` (`incident_date`);

--
-- Indexes for table `duty_assignments`
--
ALTER TABLE `duty_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `exam_type_id` (`exam_type_id`),
  ADD KEY `term_year` (`term`,`year`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_exam_student` (`exam_id`,`student_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `exam_types`
--
ALTER TABLE `exam_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_exam_type` (`school_id`,`exam_type_code`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `idx_fee_type` (`fee_type`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `fee_structure`
--
ALTER TABLE `fee_structure`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_class_id` (`class_id`),
  ADD KEY `idx_fee_type` (`fee_type`);

--
-- Indexes for table `finance_managers`
--
ALTER TABLE `finance_managers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `finance_manager_logins`
--
ALTER TABLE `finance_manager_logins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_finance_manager_id` (`finance_manager_id`);

--
-- Indexes for table `finance_manager_sessions`
--
ALTER TABLE `finance_manager_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_finance_manager_id` (`finance_manager_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `grading_scales`
--
ALTER TABLE `grading_scales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_subject_id` (`subject_id`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_holidays_school` (`school_id`),
  ADD KEY `idx_holidays_dates` (`start_date`,`end_date`);

--
-- Indexes for table `incident_reports`
--
ALTER TABLE `incident_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `status` (`status`),
  ADD KEY `issue_date` (`issue_date`),
  ADD KEY `due_date` (`due_date`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `fee_structure_id` (`fee_structure_id`);

--
-- Indexes for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `leaveout_chits`
--
ALTER TABLE `leaveout_chits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `librarians`
--
ALTER TABLE `librarians`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `librarian_logins`
--
ALTER TABLE `librarian_logins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_librarian_id` (`librarian_id`);

--
-- Indexes for table `librarian_sessions`
--
ALTER TABLE `librarian_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_librarian_id` (`librarian_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `library_fines`
--
ALTER TABLE `library_fines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_fines_school_user` (`school_id`,`user_id`),
  ADD KEY `idx_transaction_reference` (`transaction_reference`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `parent_logins`
--
ALTER TABLE `parent_logins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_parent_id` (`parent_id`);

--
-- Indexes for table `parent_sessions`
--
ALTER TABLE `parent_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_expires` (`expires_at`);

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
-- Indexes for table `reminder_history`
--
ALTER TABLE `reminder_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_year` (`school_id`,`year`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_file_hash` (`file_hash`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_code` (`school_code`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `school_b2c_responses`
--
ALTER TABLE `school_b2c_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_withdrawal_id` (`withdrawal_id`),
  ADD KEY `idx_callback_type` (`callback_type`),
  ADD KEY `idx_result_code` (`result_code`),
  ADD KEY `idx_originator_conversation_id` (`originator_conversation_id`),
  ADD KEY `idx_conversation_id` (`conversation_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `school_balances`
--
ALTER TABLE `school_balances`
  ADD PRIMARY KEY (`school_id`),
  ADD KEY `idx_balance` (`balance`);

--
-- Indexes for table `school_breaks`
--
ALTER TABLE `school_breaks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_break` (`school_id`,`break_name`,`start_time`),
  ADD KEY `idx_school_id` (`school_id`);

--
-- Indexes for table `school_events`
--
ALTER TABLE `school_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_events_school` (`school_id`),
  ADD KEY `idx_school_events_date` (`event_date`);

--
-- Indexes for table `school_sessions`
--
ALTER TABLE `school_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `school_withdrawals`
--
ALTER TABLE `school_withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_number` (`reference_number`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_finance_manager_id` (`finance_manager_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `smtp_settings`
--
ALTER TABLE `smtp_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_school_email` (`school_id`,`email`);

--
-- Indexes for table `streams`
--
ALTER TABLE `streams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_class_id` (`class_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_admission` (`school_id`,`admission_number`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_class_id` (`class_id`),
  ADD KEY `idx_stream_id` (`stream_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `student_parents`
--
ALTER TABLE `student_parents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_parent_id` (`parent_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_class_id` (`class_id`),
  ADD KEY `idx_stream_id` (`stream_id`),
  ADD KEY `idx_teacher_type` (`teacher_type`);

--
-- Indexes for table `teacher_logins`
--
ALTER TABLE `teacher_logins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_teacher_id` (`teacher_id`);

--
-- Indexes for table `teacher_sessions`
--
ALTER TABLE `teacher_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_teacher_id` (`teacher_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_teacher_id` (`teacher_id`),
  ADD KEY `idx_class_id` (`class_id`),
  ADD KEY `idx_subject_id` (`subject_id`);

--
-- Indexes for table `terms`
--
ALTER TABLE `terms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_school_year_term` (`school_id`,`year`,`term_name`);

--
-- Indexes for table `timetables`
--
ALTER TABLE `timetables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_class_id` (`class_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `timetable_assignments`
--
ALTER TABLE `timetable_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `slot_id` (`slot_id`),
  ADD KEY `idx_timetable_id` (`timetable_id`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_class_id` (`class_id`),
  ADD KEY `idx_teacher_id` (`teacher_id`),
  ADD KEY `idx_subject_id` (`subject_id`);

--
-- Indexes for table `timetable_slots`
--
ALTER TABLE `timetable_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slot` (`school_id`,`day_of_week`,`start_time`,`end_time`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_day_of_week` (`day_of_week`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_checkout_request_id` (`CheckoutRequestID`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_phone` (`PhoneNumber`);

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
-- AUTO_INCREMENT for table `academic_performance`
--
ALTER TABLE `academic_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `assignment_comments`
--
ALTER TABLE `assignment_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assignment_downloads`
--
ALTER TABLE `assignment_downloads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `book_borrowings`
--
ALTER TABLE `book_borrowings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `book_categories`
--
ALTER TABLE `book_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `book_history`
--
ALTER TABLE `book_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- AUTO_INCREMENT for table `book_reservations`
--
ALTER TABLE `book_reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cleanliness_checks`
--
ALTER TABLE `cleanliness_checks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disciplinary_action_types`
--
ALTER TABLE `disciplinary_action_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `disciplinary_committee`
--
ALTER TABLE `disciplinary_committee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disciplinary_records`
--
ALTER TABLE `disciplinary_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `duty_assignments`
--
ALTER TABLE `duty_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_results`
--
ALTER TABLE `exam_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_types`
--
ALTER TABLE `exam_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fee_payments`
--
ALTER TABLE `fee_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `fee_structure`
--
ALTER TABLE `fee_structure`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `finance_managers`
--
ALTER TABLE `finance_managers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `finance_manager_logins`
--
ALTER TABLE `finance_manager_logins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `finance_manager_sessions`
--
ALTER TABLE `finance_manager_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `grading_scales`
--
ALTER TABLE `grading_scales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `incident_reports`
--
ALTER TABLE `incident_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `leaveout_chits`
--
ALTER TABLE `leaveout_chits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `librarians`
--
ALTER TABLE `librarians`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `librarian_logins`
--
ALTER TABLE `librarian_logins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `librarian_sessions`
--
ALTER TABLE `librarian_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `library_fines`
--
ALTER TABLE `library_fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `parent_logins`
--
ALTER TABLE `parent_logins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parent_sessions`
--
ALTER TABLE `parent_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reminder_history`
--
ALTER TABLE `reminder_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `school_b2c_responses`
--
ALTER TABLE `school_b2c_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `school_breaks`
--
ALTER TABLE `school_breaks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `school_events`
--
ALTER TABLE `school_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_sessions`
--
ALTER TABLE `school_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `school_withdrawals`
--
ALTER TABLE `school_withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `smtp_settings`
--
ALTER TABLE `smtp_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `streams`
--
ALTER TABLE `streams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_parents`
--
ALTER TABLE `student_parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `teacher_logins`
--
ALTER TABLE `teacher_logins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `teacher_sessions`
--
ALTER TABLE `teacher_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `terms`
--
ALTER TABLE `terms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `timetables`
--
ALTER TABLE `timetables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `timetable_assignments`
--
ALTER TABLE `timetable_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `timetable_slots`
--
ALTER TABLE `timetable_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=196;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_performance`
--
ALTER TABLE `academic_performance`
  ADD CONSTRAINT `fk_academic_performance_exam_type` FOREIGN KEY (`exam_type_id`) REFERENCES `exam_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_performance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD CONSTRAINT `academic_years_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_4` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assignment_comments`
--
ALTER TABLE `assignment_comments`
  ADD CONSTRAINT `fk_comment_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assignment_downloads`
--
ALTER TABLE `assignment_downloads`
  ADD CONSTRAINT `assignment_downloads_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `fk_book_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `book_borrowings`
--
ALTER TABLE `book_borrowings`
  ADD CONSTRAINT `fk_borrowing_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `book_categories`
--
ALTER TABLE `book_categories`
  ADD CONSTRAINT `book_categories_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `book_history`
--
ALTER TABLE `book_history`
  ADD CONSTRAINT `book_history_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `book_history_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `book_reservations`
--
ALTER TABLE `book_reservations`
  ADD CONSTRAINT `book_reservations_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `book_reservations_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `fk_classes_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cleanliness_checks`
--
ALTER TABLE `cleanliness_checks`
  ADD CONSTRAINT `cleanliness_checks_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  ADD CONSTRAINT `cleanliness_checks_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`);

--
-- Constraints for table `disciplinary_action_types`
--
ALTER TABLE `disciplinary_action_types`
  ADD CONSTRAINT `disciplinary_action_types_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `disciplinary_committee`
--
ALTER TABLE `disciplinary_committee`
  ADD CONSTRAINT `disciplinary_committee_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `disciplinary_records`
--
ALTER TABLE `disciplinary_records`
  ADD CONSTRAINT `disciplinary_records_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disciplinary_records_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `duty_assignments`
--
ALTER TABLE `duty_assignments`
  ADD CONSTRAINT `duty_assignments_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  ADD CONSTRAINT `duty_assignments_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  ADD CONSTRAINT `duty_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `schools` (`id`);

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`exam_type_id`) REFERENCES `exam_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD CONSTRAINT `fk_payment_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fee_structure`
--
ALTER TABLE `fee_structure`
  ADD CONSTRAINT `fk_fee_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fee_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `finance_managers`
--
ALTER TABLE `finance_managers`
  ADD CONSTRAINT `fk_finance_managers_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `finance_manager_logins`
--
ALTER TABLE `finance_manager_logins`
  ADD CONSTRAINT `fk_finance_manager_login_finance_manager` FOREIGN KEY (`finance_manager_id`) REFERENCES `finance_managers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `finance_manager_sessions`
--
ALTER TABLE `finance_manager_sessions`
  ADD CONSTRAINT `fk_finance_manager_session_finance_manager` FOREIGN KEY (`finance_manager_id`) REFERENCES `finance_managers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grading_scales`
--
ALTER TABLE `grading_scales`
  ADD CONSTRAINT `fk_grading_scales_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_grading_scales_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `holidays`
--
ALTER TABLE `holidays`
  ADD CONSTRAINT `holidays_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_reports`
--
ALTER TABLE `incident_reports`
  ADD CONSTRAINT `incident_reports_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  ADD CONSTRAINT `incident_reports_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structure` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  ADD CONSTRAINT `invoice_payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_payments_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `fee_payments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leaveout_chits`
--
ALTER TABLE `leaveout_chits`
  ADD CONSTRAINT `leaveout_chits_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  ADD CONSTRAINT `leaveout_chits_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  ADD CONSTRAINT `leaveout_chits_ibfk_3` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `librarians`
--
ALTER TABLE `librarians`
  ADD CONSTRAINT `fk_librarian_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `librarian_logins`
--
ALTER TABLE `librarian_logins`
  ADD CONSTRAINT `fk_librarian_login_librarian` FOREIGN KEY (`librarian_id`) REFERENCES `librarians` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `librarian_sessions`
--
ALTER TABLE `librarian_sessions`
  ADD CONSTRAINT `fk_librarian_session_librarian` FOREIGN KEY (`librarian_id`) REFERENCES `librarians` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `library_fines`
--
ALTER TABLE `library_fines`
  ADD CONSTRAINT `library_fines_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `library_fines_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parents`
--
ALTER TABLE `parents`
  ADD CONSTRAINT `fk_parents_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parent_logins`
--
ALTER TABLE `parent_logins`
  ADD CONSTRAINT `fk_parent_login_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parent_sessions`
--
ALTER TABLE `parent_sessions`
  ADD CONSTRAINT `fk_parent_session_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reminder_history`
--
ALTER TABLE `reminder_history`
  ADD CONSTRAINT `reminder_history_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reminder_history_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `school_balances`
--
ALTER TABLE `school_balances`
  ADD CONSTRAINT `fk_school_balances_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `school_breaks`
--
ALTER TABLE `school_breaks`
  ADD CONSTRAINT `school_breaks_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `school_events`
--
ALTER TABLE `school_events`
  ADD CONSTRAINT `school_events_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `school_sessions`
--
ALTER TABLE `school_sessions`
  ADD CONSTRAINT `fk_session_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `smtp_settings`
--
ALTER TABLE `smtp_settings`
  ADD CONSTRAINT `smtp_settings_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `streams`
--
ALTER TABLE `streams`
  ADD CONSTRAINT `fk_streams_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_students_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_students_stream` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_parents`
--
ALTER TABLE `student_parents`
  ADD CONSTRAINT `fk_sp_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sp_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `fk_subjects_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `fk_teachers_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_teachers_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_teachers_stream` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teacher_logins`
--
ALTER TABLE `teacher_logins`
  ADD CONSTRAINT `fk_teacher_login_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_sessions`
--
ALTER TABLE `teacher_sessions`
  ADD CONSTRAINT `fk_teacher_session_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `fk_teacher_subjects_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_teacher_subjects_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_teacher_subjects_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `terms`
--
ALTER TABLE `terms`
  ADD CONSTRAINT `terms_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetables`
--
ALTER TABLE `timetables`
  ADD CONSTRAINT `timetables_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetables_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable_assignments`
--
ALTER TABLE `timetable_assignments`
  ADD CONSTRAINT `timetable_assignments_ibfk_1` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_assignments_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_assignments_ibfk_3` FOREIGN KEY (`slot_id`) REFERENCES `timetable_slots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_assignments_ibfk_4` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_assignments_ibfk_5` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_assignments_ibfk_6` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable_slots`
--
ALTER TABLE `timetable_slots`
  ADD CONSTRAINT `timetable_slots_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
