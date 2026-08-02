-- Kenya EduHub Database Backup
-- Generated: 2026-08-02 22:11:15
-- Database: users_db

-- Table structure for `academic_performance`
DROP TABLE IF EXISTS `academic_performance`;
CREATE TABLE `academic_performance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `term` varchar(50) NOT NULL,
  `year` int(4) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `exam_type_id` int(11) DEFAULT NULL,
  `marks` decimal(5,2) NOT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_term_year` (`term`,`year`),
  KEY `exam_type_id` (`exam_type_id`),
  CONSTRAINT `fk_academic_performance_exam_type` FOREIGN KEY (`exam_type_id`) REFERENCES `exam_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_performance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `academic_performance` VALUES ("1","4","Term 1","2026","PHYSICS","","60.00","A","Exelent","2026-07-04 23:06:10");
INSERT INTO `academic_performance` VALUES ("5","4","Term 1","2026","CHEMISTRY","","65.00","A","Exelent","2026-07-19 18:10:34");
INSERT INTO `academic_performance` VALUES ("6","4","Term 2","2026","PHYSICS","1","50.00","B+","Exelent","2026-07-24 22:54:01");
INSERT INTO `academic_performance` VALUES ("7","4","Term 2","2026","MATHEMATICS","1","50.00","B-","Very Good","2026-07-25 01:08:37");
INSERT INTO `academic_performance` VALUES ("8","4","Term 2","2026","KISWAHILI","1","60.00","B-","Very Good","2026-07-25 01:10:07");
INSERT INTO `academic_performance` VALUES ("9","4","Term 2","2026","HISTORY AND GOVERNMENT","1","80.00","A","Exelent","2026-07-25 01:11:01");
INSERT INTO `academic_performance` VALUES ("10","4","Term 2","2026","GEOGRAPHY","1","60.00","B+","Exelent","2026-07-25 01:11:47");
INSERT INTO `academic_performance` VALUES ("11","4","Term 2","2026","ENGLISH","1","55.00","C+","Good","2026-07-25 01:12:33");
INSERT INTO `academic_performance` VALUES ("12","4","Term 2","2026","CRE","1","70.00","B-","Very Good","2026-07-25 01:13:26");
INSERT INTO `academic_performance` VALUES ("13","4","Term 2","2026","CHEMISTRY","1","70.00","A","Exelent","2026-07-25 01:14:07");
INSERT INTO `academic_performance` VALUES ("14","4","Term 2","2026","BUSINESSES STUDIES","1","58.00","C+","Good","2026-07-25 01:15:02");
INSERT INTO `academic_performance` VALUES ("15","4","Term 2","2026","BIOLOGY","1","77.00","A-","Excellent","2026-07-25 01:15:48");
INSERT INTO `academic_performance` VALUES ("16","4","Term 2","2026","AGRICULTURE","1","62.00","C","Good","2026-07-25 01:17:06");


-- Table structure for `academic_years`
DROP TABLE IF EXISTS `academic_years`;
CREATE TABLE `academic_years` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_school_year` (`school_id`,`year`),
  CONSTRAINT `academic_years_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for `admin_backup_settings`
DROP TABLE IF EXISTS `admin_backup_settings`;
CREATE TABLE `admin_backup_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_enabled` int(11) DEFAULT 0,
  `backup_frequency` varchar(20) DEFAULT 'daily',
  `backup_path` varchar(255) DEFAULT NULL,
  `backup_retention_days` int(11) DEFAULT 7,
  `auto_backup` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admin_backup_settings` VALUES ("1","1","daily","../backups","7","1","2026-07-29 22:14:47","2026-07-29 22:16:33");


-- Table structure for `admin_site_settings`
DROP TABLE IF EXISTS `admin_site_settings`;
CREATE TABLE `admin_site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_name` varchar(255) DEFAULT 'Kenya EduHub',
  `site_description` text DEFAULT NULL,
  `admin_email` varchar(255) DEFAULT NULL,
  `max_file_size` int(11) DEFAULT 10,
  `allowed_extensions` varchar(255) DEFAULT 'pdf,doc,docx,ppt,pptx,xls,xlsx,txt',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admin_site_settings` VALUES ("1","Kenya EduHub","Kenya\'s comprehensive education platform","admin@kenyaeduhub.com","10","pdf,doc,docx,ppt,pptx,xls,xlsx,txt","2026-07-31 14:24:09","2026-07-31 14:24:09");


-- Table structure for `admin_sms_settings`
DROP TABLE IF EXISTS `admin_sms_settings`;
CREATE TABLE `admin_sms_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sms_provider` varchar(20) DEFAULT 'mobitech',
  `mobitech_api_key` varchar(255) DEFAULT NULL,
  `mobitech_sender_id` varchar(11) DEFAULT NULL,
  `textsms_api_key` varchar(255) DEFAULT NULL,
  `textsms_partner_id` varchar(255) DEFAULT NULL,
  `textsms_sender_id` varchar(11) DEFAULT NULL,
  `sms_enabled` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admin_sms_settings` VALUES ("1","mobitech","fbec514c17f9f084b9d8745664b634e34e16b5d089b47224ec3b47b9303f1b54","FULL_CIRCLE","59c5825a8c0d1491e6d47cc0889c1ee0","13994","TextSMS","1","2026-07-27 19:26:47","2026-07-28 00:12:21");


-- Table structure for `admin_smtp_settings`
DROP TABLE IF EXISTS `admin_smtp_settings`;
CREATE TABLE `admin_smtp_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `smtp_host` varchar(255) NOT NULL,
  `smtp_port` int(11) NOT NULL DEFAULT 587,
  `smtp_username` varchar(255) NOT NULL,
  `smtp_password` varchar(255) NOT NULL,
  `email_from` varchar(255) NOT NULL,
  `encryption` varchar(10) DEFAULT 'tls',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admin_smtp_settings` VALUES ("1","smtp.gmail.com","587","otienobrian029@gmail.com","dwuunoftzkodeome","otienobrian029@gmail.com","tls","2026-07-27 18:18:54","2026-07-27 18:48:58");


-- Table structure for `admin_system_settings`
DROP TABLE IF EXISTS `admin_system_settings`;
CREATE TABLE `admin_system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `maintenance_mode` int(11) DEFAULT 0,
  `debug_mode` int(11) DEFAULT 0,
  `session_timeout` int(11) DEFAULT 30,
  `max_login_attempts` int(11) DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admin_system_settings` VALUES ("1","0","0","30","4","2026-07-29 09:08:55","2026-07-30 17:49:12");


-- Table structure for `aggregate_points_distribution`
DROP TABLE IF EXISTS `aggregate_points_distribution`;
CREATE TABLE `aggregate_points_distribution` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `grade_name` varchar(10) NOT NULL,
  `min_points` int(11) NOT NULL,
  `max_points` int(11) NOT NULL,
  `grade_description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `aggregate_points_school_fk` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `aggregate_points_distribution` VALUES ("1","1","A","78","84","Excellent","2026-07-25 16:29:46","2026-07-25 16:29:46");
INSERT INTO `aggregate_points_distribution` VALUES ("2","1","A-","71","77","Very Good","2026-07-25 16:29:46","2026-07-25 16:29:46");
INSERT INTO `aggregate_points_distribution` VALUES ("4","1","B","57","63","Fairly Good","2026-07-25 16:29:46","2026-07-26 02:38:22");
INSERT INTO `aggregate_points_distribution` VALUES ("5","1","B-","50","56","Fair","2026-07-25 16:29:46","2026-07-25 16:29:46");
INSERT INTO `aggregate_points_distribution` VALUES ("7","1","C","36","42","Below Average","2026-07-25 16:29:46","2026-07-26 02:38:22");
INSERT INTO `aggregate_points_distribution` VALUES ("8","1","C-","29","35","Poor","2026-07-25 16:29:46","2026-07-25 16:29:46");
INSERT INTO `aggregate_points_distribution` VALUES ("10","1","D","15","21","Extremely Poor","2026-07-25 16:29:46","2026-07-26 02:38:22");
INSERT INTO `aggregate_points_distribution` VALUES ("11","1","D-","8","14","Fail","2026-07-25 16:29:46","2026-07-25 16:29:46");
INSERT INTO `aggregate_points_distribution` VALUES ("12","1","E","0","7","Fail","2026-07-25 16:29:46","2026-07-25 16:29:46");
INSERT INTO `aggregate_points_distribution` VALUES ("13","1","B+","64","70","Good","2026-07-26 02:38:22","2026-07-26 02:38:22");
INSERT INTO `aggregate_points_distribution` VALUES ("14","1","C+","43","49","Average","2026-07-26 02:38:22","2026-07-26 02:38:22");
INSERT INTO `aggregate_points_distribution` VALUES ("15","1","D+","22","28","Below Average","2026-07-26 02:38:22","2026-07-26 02:38:22");


-- Table structure for `assignment_comments`
DROP TABLE IF EXISTS `assignment_comments`;
CREATE TABLE `assignment_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `author_type` enum('teacher','parent','student') DEFAULT 'teacher',
  `author_name` varchar(255) DEFAULT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_assignment_id` (`assignment_id`),
  KEY `idx_author_id` (`author_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_comment_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `assignment_comments` VALUES ("1","6","1","teacher","ROBINSON OMOLLO","TEST","2026-07-22 02:38:12");


-- Table structure for `assignment_downloads`
DROP TABLE IF EXISTS `assignment_downloads`;
CREATE TABLE `assignment_downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) NOT NULL,
  `user_type` enum('teacher','parent','student') NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `download_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_assignment` (`assignment_id`),
  KEY `idx_user` (`user_type`,`user_id`),
  KEY `idx_download_date` (`download_date`),
  CONSTRAINT `assignment_downloads_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `assignment_downloads` VALUES ("7","6","parent","2","SAMUEL OKECH","2026-07-20 04:58:58");
INSERT INTO `assignment_downloads` VALUES ("8","6","teacher","1","ROBINSON OMOLLO","2026-07-22 02:28:30");
INSERT INTO `assignment_downloads` VALUES ("9","4","parent","2","SAMUEL OKECH","2026-07-22 02:55:23");


-- Table structure for `assignments`
DROP TABLE IF EXISTS `assignments`;
CREATE TABLE `assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `idx_school_teacher` (`school_id`,`teacher_id`),
  KEY `idx_class` (`class_id`),
  KEY `idx_subject` (`subject_id`),
  KEY `idx_type` (`assignment_type`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignments_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignments_ibfk_4` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `assignments` VALUES ("4","1","1","1","","chemisry asinment","this is chemistry asinment","holiday","6a5d7c0521615_1784511493.pdf","Automated_Garden_Watering_System..pdf","2026-08-01","2026-07-20 04:38:13","2026-07-20 04:38:13");
INSERT INTO `assignments` VALUES ("6","1","1","1","","physiscs asinment","this si test for physics asinment","notes","6a5d7d0d7c2e3_1784511757.docx","LINKS.docx","2026-08-08","2026-07-20 04:42:37","2026-07-20 04:42:37");


-- Table structure for `attendance`
DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`student_id`,`date`),
  KEY `idx_date` (`date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `attendance` VALUES ("1","4","2026-07-04","present","","2026-07-04 22:50:59");
INSERT INTO `attendance` VALUES ("2","4","2026-07-05","excused","","2026-07-05 16:11:25");
INSERT INTO `attendance` VALUES ("3","4","2026-07-06","present","","2026-07-07 22:23:33");
INSERT INTO `attendance` VALUES ("4","4","2026-07-22","present","","2026-07-22 03:02:32");


-- Table structure for `book_borrowings`
DROP TABLE IF EXISTS `book_borrowings`;
CREATE TABLE `book_borrowings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_book_id` (`book_id`),
  KEY `idx_borrower_type` (`borrower_type`),
  KEY `idx_borrower_id` (`borrower_id`),
  KEY `idx_status` (`status`),
  KEY `idx_due_date` (`due_date`),
  CONSTRAINT `fk_borrowing_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `book_borrowings` VALUES ("3","4","student","4","2026-07-12","2026-07-17","2026-07-24","overdue","TEST","good","2026-07-22 19:03:14","2026-07-24 02:48:40");
INSERT INTO `book_borrowings` VALUES ("4","4","student","4","2026-07-24","2026-07-30","2026-07-24","returned","TEST","good","2026-07-24 02:46:00","2026-07-24 02:48:25");
INSERT INTO `book_borrowings` VALUES ("5","4","student","4","2026-07-04","2026-07-14","2026-07-24","","test","damaged","2026-07-24 03:01:47","2026-07-24 17:05:55");
INSERT INTO `book_borrowings` VALUES ("6","5","student","4","2026-06-24","2026-07-09","2026-07-24","overdue","","good","2026-07-24 03:03:15","2026-07-24 03:09:53");
INSERT INTO `book_borrowings` VALUES ("7","4","student","4","2026-07-04","2026-07-14","2026-07-24","","TEST","lost","2026-07-24 21:24:31","2026-07-24 21:25:38");
INSERT INTO `book_borrowings` VALUES ("8","4","student","4","2026-07-04","2026-07-14","2026-07-24","","TEST","damaged","2026-07-24 21:27:08","2026-07-24 21:27:47");


-- Table structure for `book_categories`
DROP TABLE IF EXISTS `book_categories`;
CREATE TABLE `book_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_category` (`school_id`,`category_name`),
  CONSTRAINT `book_categories_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `book_categories` VALUES ("1","1","TEST","FOR SCHOOL","active","2026-07-22 17:18:42","2026-07-22 17:18:42");
INSERT INTO `book_categories` VALUES ("2","1","Fiction","Test category","active","2026-07-22 19:01:41","2026-07-22 19:01:41");


-- Table structure for `book_history`
DROP TABLE IF EXISTS `book_history`;
CREATE TABLE `book_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `action` enum('added','edited','deleted','borrowed','returned','reserved','lost','damaged') NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('librarian','student','teacher') NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_book_history_book_id` (`book_id`),
  KEY `idx_book_history_school_id` (`school_id`),
  CONSTRAINT `book_history_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `book_history_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=178 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `book_history` VALUES ("6","4","1","","0","","Fine issued: 50 for overdue borrowing ID: 3","2026-07-22 19:03:14");
INSERT INTO `book_history` VALUES ("8","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 19:13:33");
INSERT INTO `book_history` VALUES ("9","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 19:13:42");
INSERT INTO `book_history` VALUES ("10","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 19:18:45");
INSERT INTO `book_history` VALUES ("11","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 19:18:55");
INSERT INTO `book_history` VALUES ("12","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 19:19:40");
INSERT INTO `book_history` VALUES ("13","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 19:20:04");
INSERT INTO `book_history` VALUES ("14","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 19:22:54");
INSERT INTO `book_history` VALUES ("15","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 19:23:04");
INSERT INTO `book_history` VALUES ("16","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 19:29:02");
INSERT INTO `book_history` VALUES ("17","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 19:29:12");
INSERT INTO `book_history` VALUES ("18","4","1","","1","librarian","MPESA payment initiated: 2 for fine ID: 6","2026-07-22 19:29:47");
INSERT INTO `book_history` VALUES ("19","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 19:30:12");
INSERT INTO `book_history` VALUES ("20","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 19:31:24");
INSERT INTO `book_history` VALUES ("21","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 19:31:32");
INSERT INTO `book_history` VALUES ("22","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 19:34:02");
INSERT INTO `book_history` VALUES ("23","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 19:34:29");
INSERT INTO `book_history` VALUES ("24","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 19:38:10");
INSERT INTO `book_history` VALUES ("25","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 19:38:18");
INSERT INTO `book_history` VALUES ("26","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 19:40:04");
INSERT INTO `book_history` VALUES ("27","4","1","","0","","MPESA payment successful: 1, Receipt: UGMN80E49M for fine ID: 6","2026-07-22 19:40:16");
INSERT INTO `book_history` VALUES ("28","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 19:41:18");
INSERT INTO `book_history` VALUES ("29","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 19:41:32");
INSERT INTO `book_history` VALUES ("30","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 19:41:48");
INSERT INTO `book_history` VALUES ("31","4","1","","0","","MPESA payment successful: 1, Receipt: UGMN80DZXL for fine ID: 6","2026-07-22 19:42:04");
INSERT INTO `book_history` VALUES ("32","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 19:44:45");
INSERT INTO `book_history` VALUES ("33","4","1","","0","","MPESA payment failed: The initiator information is invalid. for fine ID: 6","2026-07-22 19:44:56");
INSERT INTO `book_history` VALUES ("34","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 19:51:13");
INSERT INTO `book_history` VALUES ("35","4","1","","0","","MPESA payment successful: 1, Receipt: UGMN80E4ND for fine ID: 6","2026-07-22 19:51:25");
INSERT INTO `book_history` VALUES ("36","4","1","","1","librarian","MPESA payment initiated: 47 for fine ID: 6","2026-07-22 19:55:40");
INSERT INTO `book_history` VALUES ("37","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 19:55:49");
INSERT INTO `book_history` VALUES ("38","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 19:59:44");
INSERT INTO `book_history` VALUES ("39","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 19:59:56");
INSERT INTO `book_history` VALUES ("40","4","1","","1","librarian","MPESA payment initiated: 47 for fine ID: 6","2026-07-22 20:01:05");
INSERT INTO `book_history` VALUES ("41","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 21:53:37");
INSERT INTO `book_history` VALUES ("42","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 21:53:45");
INSERT INTO `book_history` VALUES ("43","4","1","","1","librarian","MPESA payment initiated: 47 for fine ID: 6","2026-07-22 22:03:36");
INSERT INTO `book_history` VALUES ("44","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 22:03:49");
INSERT INTO `book_history` VALUES ("45","4","1","","1","librarian","MPESA payment initiated: 47 for fine ID: 6","2026-07-22 22:05:46");
INSERT INTO `book_history` VALUES ("46","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 22:05:55");
INSERT INTO `book_history` VALUES ("47","4","1","","1","librarian","MPESA payment initiated: 47 for fine ID: 6","2026-07-22 22:13:35");
INSERT INTO `book_history` VALUES ("48","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 22:13:43");
INSERT INTO `book_history` VALUES ("49","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 22:14:19");
INSERT INTO `book_history` VALUES ("50","4","1","","0","","MPESA payment successful: 1, Receipt: UGMN80ELXU for fine ID: 6","2026-07-22 22:14:35");
INSERT INTO `book_history` VALUES ("51","4","1","","1","librarian","MPESA payment initiated: 46 for fine ID: 6","2026-07-22 22:16:31");
INSERT INTO `book_history` VALUES ("52","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 22:16:39");
INSERT INTO `book_history` VALUES ("53","4","1","","1","librarian","MPESA payment initiated: 46 for fine ID: 6","2026-07-22 22:20:24");
INSERT INTO `book_history` VALUES ("54","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 22:20:45");
INSERT INTO `book_history` VALUES ("55","4","1","","1","librarian","MPESA payment initiated: 46 for fine ID: 6","2026-07-22 22:22:50");
INSERT INTO `book_history` VALUES ("56","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 22:23:02");
INSERT INTO `book_history` VALUES ("57","4","1","","1","librarian","MPESA payment initiated: 46 for fine ID: 6","2026-07-22 22:23:49");
INSERT INTO `book_history` VALUES ("58","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 22:24:02");
INSERT INTO `book_history` VALUES ("59","4","1","","1","librarian","MPESA payment initiated: 46 for fine ID: 6","2026-07-22 22:25:26");
INSERT INTO `book_history` VALUES ("60","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 22:25:37");
INSERT INTO `book_history` VALUES ("61","4","1","","1","librarian","MPESA payment initiated: 46 for fine ID: 6","2026-07-22 22:26:37");
INSERT INTO `book_history` VALUES ("62","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 22:26:51");
INSERT INTO `book_history` VALUES ("63","4","1","","1","librarian","MPESA payment initiated: 46 for fine ID: 6","2026-07-22 22:29:40");
INSERT INTO `book_history` VALUES ("64","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 22:29:49");
INSERT INTO `book_history` VALUES ("65","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 22:30:13");
INSERT INTO `book_history` VALUES ("66","4","1","","0","","MPESA payment successful: 1, Receipt: UGMN80ES0M for fine ID: 6","2026-07-22 22:30:24");
INSERT INTO `book_history` VALUES ("67","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 22:34:09");
INSERT INTO `book_history` VALUES ("68","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 22:34:30");
INSERT INTO `book_history` VALUES ("69","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-22 22:34:53");
INSERT INTO `book_history` VALUES ("70","4","1","","0","","MPESA payment successful: 1, Receipt: UGMN80ES2Y for fine ID: 6","2026-07-22 22:35:07");
INSERT INTO `book_history` VALUES ("71","4","1","","1","librarian","MPESA payment initiated: 44 for fine ID: 6","2026-07-22 22:37:32");
INSERT INTO `book_history` VALUES ("72","4","1","","0","","MPESA payment failed: No response from user. for fine ID: 6","2026-07-22 22:38:06");
INSERT INTO `book_history` VALUES ("73","4","1","","1","librarian","MPESA payment initiated: 44 for fine ID: 6","2026-07-22 22:38:09");
INSERT INTO `book_history` VALUES ("74","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 22:38:28");
INSERT INTO `book_history` VALUES ("75","4","1","","1","librarian","MPESA payment initiated: 44 for fine ID: 6","2026-07-22 23:03:11");
INSERT INTO `book_history` VALUES ("76","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-22 23:03:25");
INSERT INTO `book_history` VALUES ("77","4","1","","1","librarian","MPESA payment initiated: 44 for fine ID: 6","2026-07-23 00:13:05");
INSERT INTO `book_history` VALUES ("78","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-23 00:13:17");
INSERT INTO `book_history` VALUES ("79","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-24 01:36:44");
INSERT INTO `book_history` VALUES ("80","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 6","2026-07-24 01:36:53");
INSERT INTO `book_history` VALUES ("81","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 6","2026-07-24 01:40:06");
INSERT INTO `book_history` VALUES ("82","4","1","","0","","MPESA payment successful: 1, Receipt: UGON80IRKO for fine ID: 6","2026-07-24 01:40:18");
INSERT INTO `book_history` VALUES ("83","4","1","edited","1","librarian","Edited book: Test Book for Library System by Test Author","2026-07-24 02:37:24");
INSERT INTO `book_history` VALUES ("84","4","1","edited","1","librarian","Edited book: Test Book for Library System by Test Author","2026-07-24 15:38:48");
INSERT INTO `book_history` VALUES ("85","4","1","","0","","Fine issued: 500.00 for lost book (10 days overdue): Test Book for Library System","2026-07-24 16:19:52");
INSERT INTO `book_history` VALUES ("86","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 26","2026-07-24 16:24:31");
INSERT INTO `book_history` VALUES ("87","4","1","","0","","MPESA payment failed: No response from user. for fine ID: 26","2026-07-24 16:24:59");
INSERT INTO `book_history` VALUES ("88","4","1","","1","librarian","MPESA payment initiated: 500 for fine ID: 26","2026-07-24 16:25:12");
INSERT INTO `book_history` VALUES ("89","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 26","2026-07-24 16:25:16");
INSERT INTO `book_history` VALUES ("90","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 26","2026-07-24 16:25:36");
INSERT INTO `book_history` VALUES ("91","4","1","","0","","MPESA payment failed: No response from user. for fine ID: 26","2026-07-24 16:26:04");
INSERT INTO `book_history` VALUES ("92","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 26","2026-07-24 16:26:53");
INSERT INTO `book_history` VALUES ("93","4","1","","0","","MPESA payment failed: No response from user. for fine ID: 26","2026-07-24 16:27:22");
INSERT INTO `book_history` VALUES ("94","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 26","2026-07-24 16:27:50");
INSERT INTO `book_history` VALUES ("95","4","1","","0","","MPESA payment successful: 1, Receipt: UGON80L1VF for fine ID: 26","2026-07-24 16:28:04");
INSERT INTO `book_history` VALUES ("96","5","1","edited","1","librarian","Edited book: Overdue Test Book by Test Author","2026-07-24 21:22:28");
INSERT INTO `book_history` VALUES ("97","5","1","edited","1","librarian","Edited book: Overdue Test Book by Test Author","2026-07-24 21:22:45");
INSERT INTO `book_history` VALUES ("98","4","1","","1","librarian","Fine issued: 500.00 for lost book: ","2026-07-24 21:25:38");
INSERT INTO `book_history` VALUES ("99","4","1","","1","librarian","Fine issued: 125 for damaged book: ","2026-07-24 21:27:47");
INSERT INTO `book_history` VALUES ("100","4","1","","2","","Payment: 1 via mpesa (Parent: 2) (Phone: 0745959757, Ref: MPESA-1784934200-6389)","2026-07-25 02:03:20");
INSERT INTO `book_history` VALUES ("101","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-07-25 02:09:36");
INSERT INTO `book_history` VALUES ("102","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-07-25 02:13:33");
INSERT INTO `book_history` VALUES ("103","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-07-25 02:13:42");
INSERT INTO `book_history` VALUES ("104","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-07-25 02:15:13");
INSERT INTO `book_history` VALUES ("105","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-07-25 02:20:19");
INSERT INTO `book_history` VALUES ("106","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-07-25 02:21:47");
INSERT INTO `book_history` VALUES ("107","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 28","2026-07-25 02:23:37");
INSERT INTO `book_history` VALUES ("108","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-07-25 02:24:12");
INSERT INTO `book_history` VALUES ("109","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-07-25 02:26:11");
INSERT INTO `book_history` VALUES ("110","4","1","","2","","MPESA payment initiated: 124 for fine ID: 28","2026-07-25 02:27:38");
INSERT INTO `book_history` VALUES ("111","4","1","","2","","MPESA payment initiated: 124 for fine ID: 28","2026-07-25 02:29:00");
INSERT INTO `book_history` VALUES ("112","4","1","","2","","MPESA payment initiated: 124 for fine ID: 28","2026-07-25 02:30:10");
INSERT INTO `book_history` VALUES ("113","4","1","","2","","MPESA payment initiated: 124 for fine ID: 28","2026-07-25 02:30:56");
INSERT INTO `book_history` VALUES ("114","4","1","","2","","MPESA payment initiated: 124 for fine ID: 28","2026-07-25 02:32:53");
INSERT INTO `book_history` VALUES ("115","4","1","","2","","MPESA payment initiated: 124 for fine ID: 28","2026-07-25 02:36:23");
INSERT INTO `book_history` VALUES ("116","4","1","","2","","MPESA payment initiated: 124 for fine ID: 28","2026-07-25 02:37:25");
INSERT INTO `book_history` VALUES ("117","4","1","","2","","MPESA payment initiated: 124 for fine ID: 28","2026-07-25 02:39:48");
INSERT INTO `book_history` VALUES ("118","4","1","","2","","MPESA payment initiated: 124 for fine ID: 28","2026-07-25 02:40:37");
INSERT INTO `book_history` VALUES ("119","4","1","","2","","MPESA payment initiated: 124 for fine ID: 28","2026-07-25 02:44:52");
INSERT INTO `book_history` VALUES ("120","4","1","","2","","MPESA payment initiated: 124 for fine ID: 28","2026-07-25 02:45:53");
INSERT INTO `book_history` VALUES ("121","4","1","","2","","MPESA payment initiated: 124 for fine ID: 28","2026-07-25 02:46:29");
INSERT INTO `book_history` VALUES ("122","4","1","","2","","MPESA payment initiated: 124 for fine ID: 28","2026-07-25 02:47:12");
INSERT INTO `book_history` VALUES ("123","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-07-25 02:48:25");
INSERT INTO `book_history` VALUES ("124","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 28","2026-07-25 02:48:34");
INSERT INTO `book_history` VALUES ("125","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-07-25 02:49:33");
INSERT INTO `book_history` VALUES ("126","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 28","2026-07-25 02:49:41");
INSERT INTO `book_history` VALUES ("127","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-07-25 02:50:01");
INSERT INTO `book_history` VALUES ("128","4","1","","0","","MPESA payment failed: The initiator information is invalid. for fine ID: 28","2026-07-25 02:50:11");
INSERT INTO `book_history` VALUES ("129","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-07-25 02:50:27");
INSERT INTO `book_history` VALUES ("130","4","1","","0","","MPESA payment successful: 1, Receipt: UGPN80MX5K for fine ID: 28","2026-07-25 02:50:40");
INSERT INTO `book_history` VALUES ("131","4","1","","2","","MPESA payment initiated: 123 for fine ID: 28","2026-07-25 03:04:03");
INSERT INTO `book_history` VALUES ("132","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 28","2026-07-25 03:04:11");
INSERT INTO `book_history` VALUES ("133","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-07-25 03:31:05");
INSERT INTO `book_history` VALUES ("134","4","1","","2","","MPESA payment initiated: 123 for fine ID: 28","2026-07-25 03:32:36");
INSERT INTO `book_history` VALUES ("135","4","1","","1","librarian","MPESA payment initiated: 123 for fine ID: 28","2026-07-25 03:33:37");
INSERT INTO `book_history` VALUES ("136","4","1","","2","","MPESA payment initiated: 123 for fine ID: 28","2026-07-25 03:34:14");
INSERT INTO `book_history` VALUES ("137","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-07-25 03:35:58");
INSERT INTO `book_history` VALUES ("138","4","1","","1","librarian","MPESA payment initiated: 123 for fine ID: 28","2026-07-25 03:37:51");
INSERT INTO `book_history` VALUES ("139","4","1","","1","librarian","MPESA payment initiated: 123 for fine ID: 28","2026-07-25 03:39:33");
INSERT INTO `book_history` VALUES ("140","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 28","2026-07-25 03:39:40");
INSERT INTO `book_history` VALUES ("141","4","1","","2","","MPESA payment initiated: 123 for fine ID: 28","2026-07-25 03:40:14");
INSERT INTO `book_history` VALUES ("142","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 28","2026-07-25 03:40:21");
INSERT INTO `book_history` VALUES ("143","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 28","2026-07-27 23:17:09");
INSERT INTO `book_history` VALUES ("144","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 28","2026-07-27 23:18:29");
INSERT INTO `book_history` VALUES ("145","4","1","","1","librarian","MPESA payment initiated: 123 for fine ID: 28","2026-07-27 23:19:24");
INSERT INTO `book_history` VALUES ("146","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 28","2026-07-27 23:19:34");
INSERT INTO `book_history` VALUES ("147","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 28","2026-07-27 23:19:56");
INSERT INTO `book_history` VALUES ("148","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 28","2026-07-27 23:20:06");
INSERT INTO `book_history` VALUES ("149","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 28","2026-07-28 00:05:30");
INSERT INTO `book_history` VALUES ("150","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 28","2026-07-28 00:05:37");
INSERT INTO `book_history` VALUES ("151","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 28","2026-07-28 00:05:52");
INSERT INTO `book_history` VALUES ("152","4","1","","0","","MPESA payment successful: 1, Receipt: UGSN80YOJL for fine ID: 28","2026-07-28 00:06:05");
INSERT INTO `book_history` VALUES ("153","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 28","2026-07-28 00:08:34");
INSERT INTO `book_history` VALUES ("154","4","1","","0","","MPESA payment successful: 1, Receipt: UGSN80YNXT for fine ID: 28","2026-07-28 00:08:47");
INSERT INTO `book_history` VALUES ("155","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 28","2026-07-28 00:12:42");
INSERT INTO `book_history` VALUES ("156","4","1","","0","","MPESA payment successful: 1, Receipt: UGSN80YL5F for fine ID: 28","2026-07-28 00:12:57");
INSERT INTO `book_history` VALUES ("157","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 28","2026-07-28 03:56:34");
INSERT INTO `book_history` VALUES ("158","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 28","2026-07-28 03:56:43");
INSERT INTO `book_history` VALUES ("159","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 28","2026-07-28 03:57:09");
INSERT INTO `book_history` VALUES ("160","4","1","","0","","MPESA payment successful: 1, Receipt: UGSN80YMV9 for fine ID: 28","2026-07-28 03:57:37");
INSERT INTO `book_history` VALUES ("161","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-08-02 12:06:19");
INSERT INTO `book_history` VALUES ("162","4","1","","0","","MPESA payment failed: Request Cancelled by user. for fine ID: 28","2026-08-02 12:06:31");
INSERT INTO `book_history` VALUES ("163","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-08-02 12:09:02");
INSERT INTO `book_history` VALUES ("164","4","1","","0","","MPESA payment successful: 1, Receipt: UH2N81KMHH for fine ID: 28","2026-08-02 12:09:14");
INSERT INTO `book_history` VALUES ("165","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-08-02 18:10:43");
INSERT INTO `book_history` VALUES ("166","4","1","","0","","MPESA payment successful: 1, Receipt: UH2N81M9DY for fine ID: 28","2026-08-02 18:10:58");
INSERT INTO `book_history` VALUES ("167","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 28","2026-08-02 18:16:55");
INSERT INTO `book_history` VALUES ("168","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 28","2026-08-02 18:18:29");
INSERT INTO `book_history` VALUES ("169","4","1","","0","","MPESA payment successful: 1, Receipt: UH2N81MB50 for fine ID: 28","2026-08-02 18:18:41");
INSERT INTO `book_history` VALUES ("170","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-08-02 18:33:11");
INSERT INTO `book_history` VALUES ("171","4","1","","0","","MPESA payment successful: 1, Receipt: UH2N81MCZ1 for fine ID: 28","2026-08-02 18:33:21");
INSERT INTO `book_history` VALUES ("172","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-08-02 18:38:43");
INSERT INTO `book_history` VALUES ("173","4","1","","0","","MPESA payment successful: 50, Receipt: TEST123456 for fine ID: 28","2026-08-02 18:44:17");
INSERT INTO `book_history` VALUES ("174","4","1","","2","","MPESA payment initiated: 1 for fine ID: 28","2026-08-02 18:44:55");
INSERT INTO `book_history` VALUES ("175","4","1","","0","","MPESA payment successful: 1, Receipt: UH2N81MACX for fine ID: 28","2026-08-02 18:45:06");
INSERT INTO `book_history` VALUES ("176","4","1","","1","librarian","MPESA payment initiated: 1 for fine ID: 28","2026-08-02 18:48:31");
INSERT INTO `book_history` VALUES ("177","4","1","","0","","MPESA payment successful: 1, Receipt: UH2N81MEDX for fine ID: 28","2026-08-02 18:48:41");


-- Table structure for `book_reservations`
DROP TABLE IF EXISTS `book_reservations`;
CREATE TABLE `book_reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('student','teacher') NOT NULL,
  `reservation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry_date` timestamp NULL DEFAULT NULL,
  `status` enum('pending','fulfilled','cancelled','expired') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `idx_book_reservations_book_id` (`book_id`),
  KEY `idx_book_reservations_status` (`status`),
  CONSTRAINT `book_reservations_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `book_reservations_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for `books`
DROP TABLE IF EXISTS `books`;
CREATE TABLE `books` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_isbn` (`isbn`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_books_category` (`category`),
  KEY `idx_books_status` (`status`),
  CONSTRAINT `fk_book_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `books` VALUES ("4","1","9780123456789","Test Book for Library System","Test Author","Test Publisher","2024","Fiction","4","7","This is a test book for testing the library management system.","uploads/book_covers/book_6a62a5b4429868.91443298.png","500.00","","","new","available","2026-07-22 19:02:09","2026-07-24 21:27:47");
INSERT INTO `books` VALUES ("5","1","OVERDUE-TEST-001","Overdue Test Book","Test Author","Test Publisher","2024","Fiction","3","3","Test book specifically for overdue testing","uploads/book_covers/book_6a63ad7579c462.17990681.png","200.00","","","new","available","2026-07-24 03:03:15","2026-07-24 21:22:45");


-- Table structure for `classes`
DROP TABLE IF EXISTS `classes`;
CREATE TABLE `classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `class_level` varchar(50) NOT NULL,
  `capacity` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_school_id` (`school_id`),
  CONSTRAINT `fk_classes_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `classes` VALUES ("1","1","GRADE 10","Secondary","300","2026-07-04 18:10:37","2026-07-04 18:10:37");
INSERT INTO `classes` VALUES ("2","1","GRADE 11","Secondary","80","2026-07-05 22:01:05","2026-07-05 22:01:05");
INSERT INTO `classes` VALUES ("3","1","GRADE 12","Secondary","80","2026-07-05 22:01:38","2026-07-05 22:01:38");


-- Table structure for `cleanliness_checks`
DROP TABLE IF EXISTS `cleanliness_checks`;
CREATE TABLE `cleanliness_checks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `area` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `cleanliness_checks_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `cleanliness_checks_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for `deleted_accounts`
DROP TABLE IF EXISTS `deleted_accounts`;
CREATE TABLE `deleted_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_user_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_code` varchar(10) DEFAULT NULL,
  `code_expires_at` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deletion_reason` text DEFAULT NULL,
  `user_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`user_data`)),
  `resources_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`resources_data`)),
  `activity_log` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`activity_log`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for `disciplinary_action_types`
DROP TABLE IF EXISTS `disciplinary_action_types`;
CREATE TABLE `disciplinary_action_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL COMMENT 'Code for the action type (e.g., warning, suspension)',
  `action_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('minor','moderate','severe','critical') DEFAULT 'moderate',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_action` (`school_id`,`action_name`),
  CONSTRAINT `disciplinary_action_types_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `disciplinary_action_types` VALUES ("1","1","warning","Warning","Formal warning for minor misconduct","minor","1","2026-07-07 15:45:06");
INSERT INTO `disciplinary_action_types` VALUES ("2","1","suspension","Suspension","Temporary removal from school for disciplinary reasons","severe","1","2026-07-07 15:45:06");
INSERT INTO `disciplinary_action_types` VALUES ("3","1","expulsion","Expulsion","Permanent removal from school","critical","1","2026-07-07 15:45:06");
INSERT INTO `disciplinary_action_types` VALUES ("4","1","probation","Probation","Student placed on probationary period","moderate","1","2026-07-07 15:45:06");
INSERT INTO `disciplinary_action_types` VALUES ("5","1","transfer","Transfer","Student transferred to another institution","moderate","1","2026-07-07 15:45:06");
INSERT INTO `disciplinary_action_types` VALUES ("6","1","death","Death","Student has passed away","critical","1","2026-07-07 15:45:06");
INSERT INTO `disciplinary_action_types` VALUES ("13","1","other","Other","Other disciplinary actions not categorized","moderate","1","2026-07-07 16:34:38");


-- Table structure for `disciplinary_committee`
DROP TABLE IF EXISTS `disciplinary_committee`;
CREATE TABLE `disciplinary_committee` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('admin','teacher','staff') NOT NULL,
  `role` enum('chair','member','secretary','observer') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `appointed_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_member` (`school_id`,`user_id`),
  CONSTRAINT `disciplinary_committee_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for `disciplinary_records`
DROP TABLE IF EXISTS `disciplinary_records`;
CREATE TABLE `disciplinary_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_by` int(11) NOT NULL COMMENT 'User ID who created the record',
  PRIMARY KEY (`id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_status` (`status`),
  KEY `idx_incident_date` (`incident_date`),
  CONSTRAINT `disciplinary_records_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `disciplinary_records_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `disciplinary_records` VALUES ("1","1","4","warning","moderate","TEST","TESTING","2026-07-02","2026-07-07","0000-00-00","TEST","TEST","pending","TESTING","","0","","","none","2026-07-07 16:52:12","2026-07-07 16:52:12","1");
INSERT INTO `disciplinary_records` VALUES ("2","1","4","suspension","minor","Test Suspension for PDF Generation","This is a test disciplinary record created for testing PDF document generation functionality. The student was suspended for testing purposes.","2026-07-07","2026-07-07","2026-07-14","Test Administrator","Principal","closed","","","0","","","none","2026-07-07 16:54:40","2026-07-07 18:05:57","1");
INSERT INTO `disciplinary_records` VALUES ("3","1","4","other","minor","TEST","TEST","2026-07-08","2026-07-08","0000-00-00","TEST","TEST","closed","TEST","","0","","","none","2026-07-08 22:57:29","2026-07-19 19:35:24","1");


-- Table structure for `duty_assignments`
DROP TABLE IF EXISTS `duty_assignments`;
CREATE TABLE `duty_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `duty_type` varchar(50) DEFAULT 'weekly',
  `week_start` date NOT NULL,
  `week_end` date NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `assigned_by` (`assigned_by`),
  CONSTRAINT `duty_assignments_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `duty_assignments_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  CONSTRAINT `duty_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `duty_assignments` VALUES ("1","1","1","weekly","2026-07-08","2026-07-14","1","active","2026-07-08 23:53:28");


-- Table structure for `exam_results`
DROP TABLE IF EXISTS `exam_results`;
CREATE TABLE `exam_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_exam_student` (`exam_id`,`student_id`),
  KEY `exam_id` (`exam_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for `exam_types`
DROP TABLE IF EXISTS `exam_types`;
CREATE TABLE `exam_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `exam_type_name` varchar(100) NOT NULL,
  `exam_type_code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_exam_type` (`school_id`,`exam_type_code`),
  KEY `school_id` (`school_id`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `exam_types` VALUES ("1","1","END TERM","001","TESTING","1","1","2026-07-24 22:10:02","2026-07-24 22:10:02");
INSERT INTO `exam_types` VALUES ("2","1","CAT","002","TESTING","1","1","2026-07-27 15:57:46","2026-07-27 15:57:46");


-- Table structure for `examination_department_heads`
DROP TABLE IF EXISTS `examination_department_heads`;
CREATE TABLE `examination_department_heads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_school_email` (`school_id`,`email`),
  KEY `idx_school_id` (`school_id`),
  CONSTRAINT `examination_department_heads_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `examination_department_heads` VALUES ("2","1","ROBINSON OMOLLO","otienobrian029@gmail.com","0745959757","$2y$10$TkytdpcW5QAhXNSQahZUy.Aw5CNCN7TPTyVetE8Jw63lA5/sXRJkm","active","2026-07-29 00:42:11","2026-07-29 00:42:11");


-- Table structure for `examiner_sessions`
DROP TABLE IF EXISTS `examiner_sessions`;
CREATE TABLE `examiner_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `examiner_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `idx_session_token` (`session_token`),
  KEY `idx_examiner_id` (`examiner_id`),
  CONSTRAINT `examiner_sessions_ibfk_1` FOREIGN KEY (`examiner_id`) REFERENCES `examination_department_heads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `examiner_sessions` VALUES ("1","2","a56e1fbd134ed5e430ab2ca1db3b1f7b33ba0f2a6fce3a1cbaab8a50a109c143","2026-07-29 23:42:39","2026-07-29 00:42:39");
INSERT INTO `examiner_sessions` VALUES ("2","2","3e7d0c29a6c616d849384661011f773ab818ccea972860e7e5569686b20b20d3","2026-07-29 23:45:08","2026-07-29 00:45:08");
INSERT INTO `examiner_sessions` VALUES ("3","2","e88a0c1ab8786c4286d09641961f443980997e0d2e934097e8f2bee9d336c75a","2026-07-29 23:55:28","2026-07-29 00:55:28");
INSERT INTO `examiner_sessions` VALUES ("4","2","0827e9efd6919550639a3c5dc27e9a463243d016c1fa5cab98e4a20aecb4fbeb","2026-07-30 08:30:10","2026-07-29 09:30:10");
INSERT INTO `examiner_sessions` VALUES ("5","2","bb8a6e2b6ff2bcccc6664f9a7eef7dd23737c54cd67ff528d7d5be9e4d2fdab4","2026-07-31 05:19:11","2026-07-30 06:19:11");
INSERT INTO `examiner_sessions` VALUES ("6","2","6ad272181a2a01ba15a747da89be906f62c82d4b12319dae444f407053af001e","2026-08-02 14:31:07","2026-08-01 15:31:07");
INSERT INTO `examiner_sessions` VALUES ("7","2","e81780991be9517a23f181f004cde575bce3c30824b2a6905870ecb9646b3915","2026-08-02 17:16:21","2026-08-01 18:16:21");


-- Table structure for `exams`
DROP TABLE IF EXISTS `exams`;
CREATE TABLE `exams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `exam_type_id` (`exam_type_id`),
  KEY `term_year` (`term`,`year`),
  KEY `status` (`status`),
  CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`exam_type_id`) REFERENCES `exam_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for `fee_adjustments`
DROP TABLE IF EXISTS `fee_adjustments`;
CREATE TABLE `fee_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `adjustment_type` enum('waiver','discount','surcharge') NOT NULL,
  `adjustment_value` decimal(10,4) NOT NULL,
  `reason` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `valid_from` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_transaction_type` (`transaction_type`),
  KEY `idx_valid_period` (`valid_from`,`valid_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for `fee_payments`
DROP TABLE IF EXISTS `fee_payments`;
CREATE TABLE `fee_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_fee_type` (`fee_type`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `fk_payment_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=141 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `fee_payments` VALUES ("1","1","4","10000.00","2026-07-04","Cash","completed","","Term 1","2026","Tuition","RCP5387B24511","2026-07-04 19:22:10");
INSERT INTO `fee_payments` VALUES ("4","1","4","10000.00","2026-07-06","M-Pesa","pending","ws_CO_06072026161947741745959757","Term 1","2026","Tuition","FEE-6A4BAB718842E-4","2026-07-06 16:19:45");
INSERT INTO `fee_payments` VALUES ("5","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026162245832745959757","Term 1","2026","RMADIAL","FEE-6A4BAC23849C8-4","2026-07-06 16:22:43");
INSERT INTO `fee_payments` VALUES ("6","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026162721054745959757","Term 1","2026","RMADIAL","FEE-6A4BAD3685A24-4","2026-07-06 16:27:18");
INSERT INTO `fee_payments` VALUES ("7","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026162937457745959757","Term 1","2026","RMADIAL","FEE-6A4BADBF3A697-4","2026-07-06 16:29:35");
INSERT INTO `fee_payments` VALUES ("9","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026164502622745959757","Term 1","2026","Tuition","FEE-6A4BB15C0D457-4","2026-07-06 16:45:00");
INSERT INTO `fee_payments` VALUES ("10","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026164809092745959757","Term 1","2026","Tuition","FEE-6A4BB216BF201-4","2026-07-06 16:48:06");
INSERT INTO `fee_payments` VALUES ("11","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026165312339745959757","Term 1","2026","Tuition","FEE-6A4BB346012F3-4","2026-07-06 16:53:10");
INSERT INTO `fee_payments` VALUES ("12","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026165804641745959757","Term 1","2026","Tuition","FEE-6A4BB46A39825-4","2026-07-06 16:58:02");
INSERT INTO `fee_payments` VALUES ("13","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026165919353745959757","Term 1","2026","Tuition","FEE-6A4BB4B5461EA-4","2026-07-06 16:59:17");
INSERT INTO `fee_payments` VALUES ("15","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026170344621745959757","Term 1","2026","Tuition","FEE-6A4BB5BE7CFA9-4","2026-07-06 17:03:42");
INSERT INTO `fee_payments` VALUES ("17","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026170821085745959757","Term 1","2026","Tuition","FEE-6A4BB6D2F0658-4","2026-07-06 17:08:18");
INSERT INTO `fee_payments` VALUES ("18","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026171221337745959757","Term 1","2026","Tuition","FEE-6A4BB7C30E2F2-4","2026-07-06 17:12:19");
INSERT INTO `fee_payments` VALUES ("19","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026171254912745959757","Term 1","2026","Tuition","FEE-6A4BB7E50A040-4","2026-07-06 17:12:53");
INSERT INTO `fee_payments` VALUES ("20","1","4","9999.00","2026-07-06","M-Pesa","pending","ws_CO_06072026171844486745959757","Term 1","2026","Tuition","FEE-6A4BB942AF300-4","2026-07-06 17:18:42");
INSERT INTO `fee_payments` VALUES ("21","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026171902319745959757","Term 1","2026","Tuition","FEE-6A4BB95414111-4","2026-07-06 17:19:00");
INSERT INTO `fee_payments` VALUES ("22","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026171929700745959757","Term 1","2026","Tuition","FEE-6A4BB96FA4ED9-4","2026-07-06 17:19:27");
INSERT INTO `fee_payments` VALUES ("23","1","4","9999.00","2026-07-06","M-Pesa","pending","ws_CO_06072026172002247745959757","Term 1","2026","Tuition","FEE-6A4BB99000731-4","2026-07-06 17:20:00");
INSERT INTO `fee_payments` VALUES ("24","1","4","9.00","2026-07-06","M-Pesa","pending","ws_CO_06072026172019159745959757","Term 1","2026","Tuition","FEE-6A4BB9A130BC9-4","2026-07-06 17:20:17");
INSERT INTO `fee_payments` VALUES ("25","1","4","100.00","2026-07-06","M-Pesa","pending","ws_CO_06072026172105511745959757","Term 1","2026","Tuition","FEE-6A4BB9CF73BF1-4","2026-07-06 17:21:03");
INSERT INTO `fee_payments` VALUES ("26","1","4","1.00","2026-07-06","M-Pesa","completed","ws_CO_06072026172559027745959757","Term 1","2026","Tuition","UG6N8AI2DU","2026-07-06 17:25:56");
INSERT INTO `fee_payments` VALUES ("27","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026173140419745959757","Term 1","2026","Tuition","FEE-6A4BBC4A1EDB6-4","2026-07-06 17:31:38");
INSERT INTO `fee_payments` VALUES ("28","1","4","9998.00","2026-07-06","M-Pesa","pending","ws_CO_06072026173208882745959757","Term 1","2026","Tuition","FEE-6A4BBC670F887-4","2026-07-06 17:32:07");
INSERT INTO `fee_payments` VALUES ("29","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026173227982745959757","Term 1","2026","Tuition","FEE-6A4BBC7999B10-4","2026-07-06 17:32:25");
INSERT INTO `fee_payments` VALUES ("30","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026174712259745959757","Term 1","2026","Tuition","FEE-6A4BBFEE0C271-4","2026-07-06 17:47:10");
INSERT INTO `fee_payments` VALUES ("31","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026175347333745959757","Term 1","2026","Tuition","FEE-6A4BC1792E164-4","2026-07-06 17:53:45");
INSERT INTO `fee_payments` VALUES ("32","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026180201106745959757","Term 1","2026","Tuition","FEE-6A4BC366AB08B-4","2026-07-06 18:01:58");
INSERT INTO `fee_payments` VALUES ("33","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026180238799745959757","Term 1","2026","Tuition","FEE-6A4BC38C7F528-4","2026-07-06 18:02:36");
INSERT INTO `fee_payments` VALUES ("34","1","4","1.00","2026-07-06","M-Pesa","failed","ws_CO_06072026180355817745959757","Term 1","2026","Tuition","","2026-07-06 18:03:53");
INSERT INTO `fee_payments` VALUES ("35","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026180700777745959757","Term 1","2026","Tuition","FEE-6A4BC4920ABC5-4","2026-07-06 18:06:58");
INSERT INTO `fee_payments` VALUES ("36","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026181305975745959757","Term 1","2026","Tuition","FEE-6A4BC5FF43952-4","2026-07-06 18:13:03");
INSERT INTO `fee_payments` VALUES ("37","1","4","1.00","2026-07-06","M-Pesa","failed","ws_CO_06072026181349475745959757","Term 1","2026","Tuition","UG6N8AI79R","2026-07-06 18:13:46");
INSERT INTO `fee_payments` VALUES ("38","1","4","1.00","2026-07-06","M-Pesa","failed","ws_CO_06072026181938961745959757","Term 1","2026","Tuition","UG6N8AIAGX","2026-07-06 18:19:36");
INSERT INTO `fee_payments` VALUES ("39","1","4","1.00","2026-07-06","M-Pesa","pending","ws_CO_06072026182650228745959757","Term 1","2026","Tuition","FEE-6A4BC937A0293-4","2026-07-06 18:26:47");
INSERT INTO `fee_payments` VALUES ("40","1","4","1.00","2026-07-06","M-Pesa","completed","ws_CO_06072026183827234745959757","Term 1","2026","Tuition","UG6N8AIHB4","2026-07-06 18:38:24");
INSERT INTO `fee_payments` VALUES ("42","1","4","1.00","2026-07-06","M-Pesa","completed","ws_CO_06072026183934251745959757","Term 1","2026","RMADIAL","UG6N8AIET0","2026-07-06 18:39:31");
INSERT INTO `fee_payments` VALUES ("45","1","4","1.00","2026-07-07","M-Pesa","completed","ws_CO_07072026134642480745959757","Term 1","2026","Tuition","UG7N8ALA2Y","2026-07-07 13:46:40");
INSERT INTO `fee_payments` VALUES ("46","1","4","1.00","2026-07-07","M-Pesa","completed","ws_CO_07072026234056220745959757","Term 1","2026","Tuition","UG7N8ANIWC","2026-07-07 23:40:54");
INSERT INTO `fee_payments` VALUES ("47","1","4","1.00","2026-07-08","M-Pesa","completed","ws_CO_TEST_1783460732","Term 1","2026","Tuition","TEST1783460732","2026-07-08 00:45:32");
INSERT INTO `fee_payments` VALUES ("48","1","4","1.00","2026-07-08","M-Pesa","completed","ws_CO_08072026004628902745959757","Term 1","2026","Tuition","UG8N8ANLTG","2026-07-08 00:46:26");
INSERT INTO `fee_payments` VALUES ("49","1","4","1.00","2026-07-08","M-Pesa","completed","ws_CO_08072026113856483745959757","Term 1","2026","Tuition","UG8N8AOV3O","2026-07-08 11:38:52");
INSERT INTO `fee_payments` VALUES ("51","1","4","1.00","2026-07-15","M-Pesa","completed","ws_CO_15072026011155751745959757","Term 1","2026","Tuition","UGFN8BG3G5","2026-07-15 01:11:53");
INSERT INTO `fee_payments` VALUES ("52","1","4","1.00","2026-07-16","M-Pesa","completed","ws_CO_16072026121807872745959757","Term 1","2026","Tuition","UGGN8BL5Y8","2026-07-16 12:18:06");
INSERT INTO `fee_payments` VALUES ("53","1","4","2.00","2026-07-16","M-Pesa","completed","ws_CO_16072026121906511745959757","Term 1","2026","Tuition","UGGN8BL4JP","2026-07-16 12:19:03");
INSERT INTO `fee_payments` VALUES ("55","1","4","1.00","2026-07-19","M-Pesa","completed","ws_CO_19072026162300421745959757","Term 1","2026","Tuition","UGJN801DNO","2026-07-19 16:22:56");
INSERT INTO `fee_payments` VALUES ("56","1","4","2.00","2026-07-19","M-Pesa","completed","ws_CO_19072026162337917745959757","Term 1","2026","Tuition","UGJN801C80","2026-07-19 16:23:34");
INSERT INTO `fee_payments` VALUES ("57","1","4","2.00","2026-07-19","M-Pesa","pending","ws_CO_19072026200130556745959757","Term 1","2026","Tuition","FEE-6A5D02E8A3038-4","2026-07-19 20:01:28");
INSERT INTO `fee_payments` VALUES ("58","1","4","2.00","2026-07-19","M-Pesa","completed","ws_CO_19072026200320607745959757","Term 1","2026","Tuition","UGJN802DVF","2026-07-19 20:03:18");
INSERT INTO `fee_payments` VALUES ("59","1","4","1.00","2026-07-19","M-Pesa","completed","ws_CO_19072026231905970745959757","Term 1","2026","Tuition","UGJN8032CK","2026-07-19 23:19:04");
INSERT INTO `fee_payments` VALUES ("60","1","4","1.00","2026-07-20","M-Pesa","pending","ws_CO_20072026050232743745959757","Term 1","2026","Tuition","FEE-6A5D81B7B1C55-4","2026-07-20 05:02:31");
INSERT INTO `fee_payments` VALUES ("61","1","4","1.00","2026-07-20","M-Pesa","pending","ws_CO_20072026050423172745959757","Term 1","2026","Tuition","FEE-6A5D8226392C9-4","2026-07-20 05:04:22");
INSERT INTO `fee_payments` VALUES ("62","1","4","1.00","2026-07-20","M-Pesa","pending","ws_CO_20072026051045220745959757","Term 1","2026","Tuition","FEE-6A5D83A48037B-4","2026-07-20 05:10:44");
INSERT INTO `fee_payments` VALUES ("63","1","4","9983.00","2026-07-20","M-Pesa","pending","ws_CO_20072026051128616745959757","Term 1","2026","Tuition","FEE-6A5D83CF9B6EE-4","2026-07-20 05:11:27");
INSERT INTO `fee_payments` VALUES ("67","1","4","1.00","2026-07-22","M-Pesa","completed","ws_CO_22072026225433145745959757","Term 1","2026","Tuition","UGMN80EP8R","2026-07-22 22:54:29");
INSERT INTO `fee_payments` VALUES ("70","1","4","1.00","2026-07-23","M-Pesa","completed","ws_CO_23072026192534871745959757","Term 1","2026","Tuition","UGNN80HUQC","2026-07-23 19:25:32");
INSERT INTO `fee_payments` VALUES ("71","1","4","1.00","2026-07-24","M-Pesa","pending","ws_CO_24072026012614195745959757","Term 2","2026","Tuition","FEE-6A629504C0EE5-4","2026-07-24 01:26:12");
INSERT INTO `fee_payments` VALUES ("72","1","4","1.00","2026-07-24","M-Pesa","pending","ws_CO_24072026012652605745959757","Term 2","2026","Tuition","FEE-6A62952B03727-4","2026-07-24 01:26:51");
INSERT INTO `fee_payments` VALUES ("73","0","4","1.00","2026-07-24","M-Pesa","pending","ws_CO_24072026012936815745959757","Term 2","2026","Tuition","FEE-6A6295CF50B13-4","2026-07-24 01:29:35");
INSERT INTO `fee_payments` VALUES ("74","0","4","1.00","2026-07-24","M-Pesa","pending","ws_CO_24072026013300460745959757","Term 2","2026","Tuition","FEE-6A62969ACA0D5-4","2026-07-24 01:32:58");
INSERT INTO `fee_payments` VALUES ("76","0","4","1.00","2026-07-24","M-Pesa","completed","ws_CO_24072026013732159745959757","Term 2","2026","Tuition","UGON80IRKH","2026-07-24 01:37:30");
INSERT INTO `fee_payments` VALUES ("77","0","4","1.00","2026-07-27","M-Pesa","pending","ws_CO_27072026220746099745959757","Term 2","2026","Tuition","FEE-6A67AC7F98C38-4","2026-07-27 22:07:43");
INSERT INTO `fee_payments` VALUES ("78","0","4","1.00","2026-07-27","M-Pesa","pending","ws_CO_27072026221619960745959757","Term 2","2026","Tuition","FEE-6A67AE80A70AB-4","2026-07-27 22:16:16");
INSERT INTO `fee_payments` VALUES ("89","0","4","1.00","2026-07-27","M-Pesa","completed","ws_CO_27072026235626477745959757","Term 2","2026","Tuition","UGRN80YGKH","2026-07-27 23:56:24");
INSERT INTO `fee_payments` VALUES ("90","0","4","1.00","2026-07-27","M-Pesa","completed","ws_CO_27072026235910034745959757","Term 2","2026","Tuition","UGRN80YL3B","2026-07-27 23:59:07");
INSERT INTO `fee_payments` VALUES ("91","0","4","1.00","2026-07-28","M-Pesa","completed","ws_CO_28072026000400307745959757","Term 2","2026","Tuition","UGSN80YGLV","2026-07-28 00:03:57");
INSERT INTO `fee_payments` VALUES ("94","0","4","10.00","2026-07-31","M-Pesa","completed","ws_CO_310720261838173745959757","Term 2","2026","Tuition","UGVN81DSUB","2026-07-31 18:38:15");
INSERT INTO `fee_payments` VALUES ("95","0","4","10.00","2026-08-01","M-Pesa","completed","ws_CO_010820261832572745959757","Term 2","2026","Tuition","UH1N81I7DG","2026-08-01 18:32:56");
INSERT INTO `fee_payments` VALUES ("103","0","4","1.00","2026-08-02","M-Pesa","pending","ws_CO_020820260510134745959757","Term 2","2026","Tuition","FEE-6A6EA7041246E-4","2026-08-02 05:10:12");
INSERT INTO `fee_payments` VALUES ("104","0","4","1.00","2026-08-02","M-Pesa","pending","ws_CO_020820260511290745959757","Term 2","2026","Tuition","FEE-6A6EA74FDA48E-4","2026-08-02 05:11:27");
INSERT INTO `fee_payments` VALUES ("108","0","4","1.00","2026-08-02","M-Pesa","completed","ws_CO_020820260517256745959757","Term 2","2026","Tuition","UH2N81JTH9","2026-08-02 05:17:24");
INSERT INTO `fee_payments` VALUES ("109","0","4","1.00","2026-08-02","M-Pesa","pending","ws_CO_020820260520469745959757","Term 2","2026","Tuition","FEE-6A6EA97D8CBCC-4","2026-08-02 05:20:45");
INSERT INTO `fee_payments` VALUES ("110","0","4","1.00","2026-08-02","M-Pesa","pending","ws_CO_020820260523093745959757","Term 2","2026","Tuition","FEE-6A6EAA0B1A153-4","2026-08-02 05:23:07");
INSERT INTO `fee_payments` VALUES ("111","0","4","1.00","2026-08-02","M-Pesa","pending","ws_CO_020820260525491745959757","Term 2","2026","Tuition","FEE-6A6EAAABE5F7D-4","2026-08-02 05:25:47");
INSERT INTO `fee_payments` VALUES ("112","0","4","1.00","2026-08-02","M-Pesa","pending","ws_CO_020820260527430745959757","Term 2","2026","Tuition","FEE-6A6EAB1DD44BC-4","2026-08-02 05:27:41");
INSERT INTO `fee_payments` VALUES ("113","0","4","1.00","2026-08-02","M-Pesa","pending","ws_CO_020820260528337745959757","Term 2","2026","Tuition","FEE-6A6EAB508E4B2-4","2026-08-02 05:28:32");
INSERT INTO `fee_payments` VALUES ("114","0","4","1.00","2026-08-02","M-Pesa","pending","ws_CO_020820260529306745959757","Term 2","2026","Tuition","FEE-6A6EAB89987FF-4","2026-08-02 05:29:29");
INSERT INTO `fee_payments` VALUES ("115","0","4","1.00","2026-08-02","M-Pesa","pending","ws_CO_020820260530476745959757","Term 2","2026","Tuition","FEE-6A6EABD64C752-4","2026-08-02 05:30:46");
INSERT INTO `fee_payments` VALUES ("116","0","4","1.00","2026-08-02","M-Pesa","pending","ws_CO_020820260531388745959757","Term 2","2026","Tuition","FEE-6A6EAC0989C1F-4","2026-08-02 05:31:37");
INSERT INTO `fee_payments` VALUES ("117","0","4","1.00","2026-08-02","M-Pesa","pending","ws_CO_020820260535281745959757","Term 2","2026","Tuition","FEE-6A6EACEEBEB82-4","2026-08-02 05:35:26");
INSERT INTO `fee_payments` VALUES ("118","0","4","1497.00","2026-08-02","M-Pesa","pending","ws_CO_020820260536579745959757","Term 2","2026","Tuition","FEE-6A6EAD48AD1ED-4","2026-08-02 05:36:56");
INSERT INTO `fee_payments` VALUES ("119","0","4","1.00","2026-08-02","M-Pesa","pending","ws_CO_020820260538064745959757","Term 2","2026","Tuition","FEE-6A6EAD8D33ECE-4","2026-08-02 05:38:05");
INSERT INTO `fee_payments` VALUES ("120","0","4","1.00","2026-08-02","M-Pesa","pending","","Term 2","2026","Tuition","FEE-6A6EF675DA076-4","2026-08-02 10:49:09");
INSERT INTO `fee_payments` VALUES ("121","0","4","1.00","2026-08-02","M-Pesa","pending","ws_CO_020820261052185745959757","Term 2","2026","Tuition","FEE-6A6EF731813F5-4","2026-08-02 10:52:17");
INSERT INTO `fee_payments` VALUES ("122","0","4","1.00","2026-08-02","M-Pesa","pending","ws_CO_020820261053392745959757","Term 2","2026","Tuition","FEE-6A6EF7821C84E-4","2026-08-02 10:53:38");
INSERT INTO `fee_payments` VALUES ("124","0","4","1.00","2026-08-02","M-Pesa","completed","ws_CO_020820261058005745959757","Term 2","2026","Tuition","UH2N81KD8M","2026-08-02 10:57:59");
INSERT INTO `fee_payments` VALUES ("125","0","4","1.00","2026-08-02","M-Pesa","completed","ws_CO_020820261101050745959757","Term 2","2026","Tuition","UH2N81KK9Y","2026-08-02 11:01:04");
INSERT INTO `fee_payments` VALUES ("126","0","4","1.00","2026-08-02","M-Pesa","pending","ws_CO_020820261121498745959757","Term 2","2026","Tuition","FEE-6A6EFE1C89F97-4","2026-08-02 11:21:48");
INSERT INTO `fee_payments` VALUES ("127","0","4","1.00","2026-08-02","M-Pesa","completed","ws_CO_020820261125506745959757","Term 2","2026","Tuition","UH2N81KKQF","2026-08-02 11:25:49");
INSERT INTO `fee_payments` VALUES ("134","0","4","1.00","2026-08-02","M-Pesa","completed","ws_CO_020820261149554745959757","Term 2","2026","Tuition","UH2N81KM5D","2026-08-02 11:49:53");
INSERT INTO `fee_payments` VALUES ("136","0","4","1.00","2026-08-02","M-Pesa","pending","ws_CO_020820261526547745959757","Term 1","2026","RMADIAL","FEE-6A6F378D34ABE-4","2026-08-02 15:26:53");
INSERT INTO `fee_payments` VALUES ("137","0","4","1.00","2026-08-02","M-Pesa","pending","ws_CO_020820261801558745959757","Term 1","2026","RMADIAL","FEE-6A6F5BE1E09DE-4","2026-08-02 18:01:53");
INSERT INTO `fee_payments` VALUES ("138","0","4","1.00","2026-08-02","M-Pesa","completed","ws_CO_020820261807145745959757","Term 1","2026","RMADIAL","UH2N81M9AK","2026-08-02 18:07:12");
INSERT INTO `fee_payments` VALUES ("139","0","4","1.00","2026-08-02","M-Pesa","completed","ws_CO_020820261812020745959757","Term 1","2026","RMADIAL","UH2N81M5EY","2026-08-02 18:11:59");
INSERT INTO `fee_payments` VALUES ("140","0","4","1.00","2026-08-02","M-Pesa","completed","ws_CO_020820262248314745959757","Term 2","2026","Tuition","UH2N81NJPH","2026-08-02 22:48:27");


-- Table structure for `fee_structure`
DROP TABLE IF EXISTS `fee_structure`;
CREATE TABLE `fee_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `term` varchar(50) NOT NULL,
  `year` int(4) NOT NULL,
  `fee_type` varchar(100) DEFAULT 'Tuition',
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_class_id` (`class_id`),
  KEY `idx_fee_type` (`fee_type`),
  CONSTRAINT `fk_fee_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fee_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `fee_structure` VALUES ("2","1","1","Term 2","2026","Tuition","15000.00","THIS SI TERM 2 FEE","2026-07-05 13:29:58");
INSERT INTO `fee_structure` VALUES ("3","1","1","Term 3","2026","Tuition","10000.00","THIS IS TERM 3 FEE\n","2026-07-05 14:02:55");
INSERT INTO `fee_structure` VALUES ("4","1","1","Term 1","2026","RMADIAL","1000.00","THIS IS REMEDIAL FEE","2026-07-05 17:42:56");
INSERT INTO `fee_structure` VALUES ("5","1","1","Term 1","2026","Tuition","21000.00","this is term 1 fee","2026-07-22 23:18:28");


-- Table structure for `finance_manager_logins`
DROP TABLE IF EXISTS `finance_manager_logins`;
CREATE TABLE `finance_manager_logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `finance_manager_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_finance_manager_id` (`finance_manager_id`),
  CONSTRAINT `fk_finance_manager_login_finance_manager` FOREIGN KEY (`finance_manager_id`) REFERENCES `finance_managers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `finance_manager_logins` VALUES ("1","1","otienobrian029@gmail.com","$2y$10$abixF.9wBTc7yj9aops9z.0Wf5TKGdundcshGzVX01Sq4.wrwE0Py","1","2026-07-05 17:16:17");


-- Table structure for `finance_manager_sessions`
DROP TABLE IF EXISTS `finance_manager_sessions`;
CREATE TABLE `finance_manager_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `finance_manager_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `idx_finance_manager_id` (`finance_manager_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_finance_manager_session_finance_manager` FOREIGN KEY (`finance_manager_id`) REFERENCES `finance_managers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `finance_manager_sessions` VALUES ("1","1","7a3b1efec46d8e3d6c1cb8435ee66a68b0c2bbbef09b6af2d69e44c20ca4098d","2026-07-06 00:16:35","2026-07-05 17:16:35");
INSERT INTO `finance_manager_sessions` VALUES ("2","1","01485a22d1af3b2c1d40dbda80a0b0cdd4350f02ab8cac2f1158bf4b8ca81d77","2026-07-06 03:39:53","2026-07-05 20:39:53");
INSERT INTO `finance_manager_sessions` VALUES ("3","1","eb16bf4570785a5b67c12487f98f9bd75fd077f5496ea524c0d574156d507d4c","2026-07-08 06:16:00","2026-07-07 23:16:00");
INSERT INTO `finance_manager_sessions` VALUES ("4","1","4390644a8803d31f7e0abfa6cc5316ed98db529273786dfa10054fc566412e1f","2026-07-09 05:20:37","2026-07-08 22:20:37");
INSERT INTO `finance_manager_sessions` VALUES ("5","1","c835f9dd6045592c4d97b642706657d38aac2be649c1abcbe67a87084de1e69b","2026-07-10 01:11:07","2026-07-09 18:11:07");
INSERT INTO `finance_manager_sessions` VALUES ("6","1","73adcb731cca6ab7475975413acb227d47d2c8ce165f65adbcf09a2ca76c4f42","2026-07-12 22:40:35","2026-07-12 15:40:35");
INSERT INTO `finance_manager_sessions` VALUES ("7","1","8b0622b0aa6975758b38d3ca718acc7e31bd568aa1108f26148e7d02bccfa56f","2026-07-15 07:59:54","2026-07-15 00:59:54");
INSERT INTO `finance_manager_sessions` VALUES ("8","1","87d181ea6484991901e72cd62191e8e8fe62a310c4dac2a6bdb2f03e2d050788","2026-07-16 16:31:28","2026-07-16 09:31:28");
INSERT INTO `finance_manager_sessions` VALUES ("9","1","bd5dce255efd23d6931b4997ac6eb02b81d17b068fe7b423e4ab98091c83a26b","2026-07-20 03:05:21","2026-07-19 20:05:21");
INSERT INTO `finance_manager_sessions` VALUES ("10","1","01ac277a201d297c5b0b403bb47f7d8828ea21fedd9f881f6210172d0e71f99a","2026-07-21 06:20:25","2026-07-20 23:20:25");
INSERT INTO `finance_manager_sessions` VALUES ("11","1","be481b1fa660f9c720d7e884f8f1428dd45e56d83915fcc1a7e35c380cb4c536","2026-07-24 07:27:33","2026-07-24 00:27:33");
INSERT INTO `finance_manager_sessions` VALUES ("12","1","e7383e7be492222e3c5ca7cc618919eb63294b567e92d72397d9390fe12f6389","2026-07-24 08:03:25","2026-07-24 01:03:25");
INSERT INTO `finance_manager_sessions` VALUES ("13","1","9390f5189cd4601be6918b8d5acdbb37fe0e447d21c3ef6e2034253047d74f9e","2026-07-24 08:59:15","2026-07-24 01:59:15");
INSERT INTO `finance_manager_sessions` VALUES ("14","1","8a5ea9554ef6d5846067e365e65f9c7d3e4d8e545040dce4031d0fca4ca7fccf","2026-07-25 10:01:57","2026-07-25 03:01:57");
INSERT INTO `finance_manager_sessions` VALUES ("15","1","e1550ec54dfdc402de02a97d48cdc9b79c6c908b4fcd58811e364c7649493030","2026-07-28 06:34:00","2026-07-27 23:34:00");
INSERT INTO `finance_manager_sessions` VALUES ("16","1","072d9f3189255c1159cd2ffdbfc5431aa92962e9a6adebd42e6bbbdd16172705","2026-07-28 22:09:56","2026-07-28 15:09:56");
INSERT INTO `finance_manager_sessions` VALUES ("17","1","166dd5c8309f6637e5e32101c3757d4d1cf2fc73d81323932b168c1ba44361b7","2026-07-29 16:42:10","2026-07-29 09:42:10");
INSERT INTO `finance_manager_sessions` VALUES ("18","1","b311ea68ca15d4f97d2ecece7d40cbfa2aa077f33d1635e33d1883c8ec37d832","2026-08-01 01:41:06","2026-07-31 18:41:06");
INSERT INTO `finance_manager_sessions` VALUES ("19","1","6370fdfbb3131214cbcd5a528b09e11c7ed83b636ac182ad232aaa5d15dfb59b","2026-08-01 22:15:44","2026-08-01 15:15:44");


-- Table structure for `finance_managers`
DROP TABLE IF EXISTS `finance_managers`;
CREATE TABLE `finance_managers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_email` (`email`),
  CONSTRAINT `fk_finance_managers_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `finance_managers` VALUES ("1","1","Brian","Onyango","otienobrian029@gmail.com","0745959757","40718992","Kisumu\n40100 kisumu","active","2026-07-05 17:16:17");


-- Table structure for `grading_scales`
DROP TABLE IF EXISTS `grading_scales`;
CREATE TABLE `grading_scales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `min_score` int(11) NOT NULL,
  `max_score` int(11) NOT NULL,
  `grade_name` varchar(50) NOT NULL,
  `grade_description` text DEFAULT NULL,
  `points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_subject_id` (`subject_id`),
  CONSTRAINT `fk_grading_scales_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grading_scales_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=160 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `grading_scales` VALUES ("28","1","3","0","14","E","Fail","1","2026-07-19 16:54:30");
INSERT INTO `grading_scales` VALUES ("29","1","3","15","19","D-","Fail","2","2026-07-19 16:54:30");
INSERT INTO `grading_scales` VALUES ("30","1","3","20","24","D","Poor","3","2026-07-19 16:54:30");
INSERT INTO `grading_scales` VALUES ("31","1","3","25","29","D+","Fair","4","2026-07-19 16:54:30");
INSERT INTO `grading_scales` VALUES ("32","1","3","30","34","C-","Good","5","2026-07-19 16:54:30");
INSERT INTO `grading_scales` VALUES ("33","1","3","35","39","C","Good","6","2026-07-19 16:54:30");
INSERT INTO `grading_scales` VALUES ("34","1","3","40","44","C+","Very Good","7","2026-07-19 16:54:30");
INSERT INTO `grading_scales` VALUES ("35","1","3","45","49","B-","Exelent","8","2026-07-19 16:54:30");
INSERT INTO `grading_scales` VALUES ("36","1","3","50","54","B","Exelent","9","2026-07-19 16:54:30");
INSERT INTO `grading_scales` VALUES ("37","1","3","55","59","B+","Exelent","10","2026-07-19 16:54:30");
INSERT INTO `grading_scales` VALUES ("38","1","3","60","64","A-","Exelent","11","2026-07-19 16:54:30");
INSERT INTO `grading_scales` VALUES ("39","1","3","65","100","A","Exelent","12","2026-07-19 16:54:30");
INSERT INTO `grading_scales` VALUES ("40","1","2","0","9","E","Fail","1","2026-07-19 17:34:06");
INSERT INTO `grading_scales` VALUES ("41","1","2","10","14","D-","Fail","2","2026-07-19 17:34:06");
INSERT INTO `grading_scales` VALUES ("42","1","2","15","19","D","Poor","3","2026-07-19 17:34:06");
INSERT INTO `grading_scales` VALUES ("43","1","2","20","24","D+","Good","4","2026-07-19 17:34:06");
INSERT INTO `grading_scales` VALUES ("44","1","2","25","29","C-","Fair","5","2026-07-19 17:34:06");
INSERT INTO `grading_scales` VALUES ("45","1","2","30","34","C","Good","6","2026-07-19 17:34:06");
INSERT INTO `grading_scales` VALUES ("46","1","2","35","39","C+","Very Good","7","2026-07-19 17:34:06");
INSERT INTO `grading_scales` VALUES ("47","1","2","40","44","B-","Exelent","8","2026-07-19 17:34:06");
INSERT INTO `grading_scales` VALUES ("48","1","2","45","49","B","Exelent","9","2026-07-19 17:34:06");
INSERT INTO `grading_scales` VALUES ("49","1","2","50","54","B+","Exelent","10","2026-07-19 17:34:06");
INSERT INTO `grading_scales` VALUES ("50","1","2","55","59","A-","Exelent","11","2026-07-19 17:34:06");
INSERT INTO `grading_scales` VALUES ("51","1","2","60","100","A","Exelent","12","2026-07-19 17:34:06");
INSERT INTO `grading_scales` VALUES ("52","1","4","0","29","E","Fail","1","2026-07-24 23:13:54");
INSERT INTO `grading_scales` VALUES ("53","1","4","30","34","D-","Fail","2","2026-07-24 23:13:54");
INSERT INTO `grading_scales` VALUES ("54","1","4","35","39","D-","Poor","3","2026-07-24 23:13:54");
INSERT INTO `grading_scales` VALUES ("55","1","4","40","44","D+","Fair","4","2026-07-24 23:13:54");
INSERT INTO `grading_scales` VALUES ("56","1","4","45","49","C-","Good","5","2026-07-24 23:13:54");
INSERT INTO `grading_scales` VALUES ("57","1","4","50","54","C","Good","6","2026-07-24 23:13:54");
INSERT INTO `grading_scales` VALUES ("58","1","4","55","59","C+","Very Good","7","2026-07-24 23:13:54");
INSERT INTO `grading_scales` VALUES ("59","1","4","60","64","B-","Very Good","8","2026-07-24 23:13:54");
INSERT INTO `grading_scales` VALUES ("60","1","4","65","69","B","Excellent","9","2026-07-24 23:13:54");
INSERT INTO `grading_scales` VALUES ("61","1","4","70","74","B+","Excellent","10","2026-07-24 23:13:54");
INSERT INTO `grading_scales` VALUES ("62","1","4","75","79","A-","Excellent","11","2026-07-24 23:13:54");
INSERT INTO `grading_scales` VALUES ("63","1","4","80","100","A","Excellent","12","2026-07-24 23:13:54");
INSERT INTO `grading_scales` VALUES ("64","1","8","0","37","E","Fail","1","2026-07-24 23:24:48");
INSERT INTO `grading_scales` VALUES ("65","1","8","38","42","D-","Fail","2","2026-07-24 23:24:48");
INSERT INTO `grading_scales` VALUES ("66","1","8","43","47","D","Poor","3","2026-07-24 23:24:48");
INSERT INTO `grading_scales` VALUES ("67","1","8","48","52","D+","Fair","4","2026-07-24 23:24:48");
INSERT INTO `grading_scales` VALUES ("68","1","8","53","57","C-","Good","5","2026-07-24 23:24:48");
INSERT INTO `grading_scales` VALUES ("69","1","8","58","62","C","Good","6","2026-07-24 23:24:48");
INSERT INTO `grading_scales` VALUES ("70","1","8","63","67","C+","Very Good","7","2026-07-24 23:24:48");
INSERT INTO `grading_scales` VALUES ("71","1","8","68","72","B-","Very Good","8","2026-07-24 23:24:48");
INSERT INTO `grading_scales` VALUES ("72","1","8","73","77","B","Very Good","9","2026-07-24 23:24:48");
INSERT INTO `grading_scales` VALUES ("73","1","8","78","82","B+","Exelent","10","2026-07-24 23:24:48");
INSERT INTO `grading_scales` VALUES ("74","1","8","83","87","A-","Exelent","11","2026-07-24 23:24:48");
INSERT INTO `grading_scales` VALUES ("75","1","8","88","100","A","Exelent","12","2026-07-24 23:24:48");
INSERT INTO `grading_scales` VALUES ("76","1","12","0","27","E","Fail","1","2026-07-24 23:36:58");
INSERT INTO `grading_scales` VALUES ("77","1","12","28","32","D-","Fail","2","2026-07-24 23:36:58");
INSERT INTO `grading_scales` VALUES ("78","1","12","33","37","D","Poor","3","2026-07-24 23:36:58");
INSERT INTO `grading_scales` VALUES ("79","1","12","38","42","D+","Poor","4","2026-07-24 23:36:58");
INSERT INTO `grading_scales` VALUES ("80","1","12","43","47","C-","Good","5","2026-07-24 23:36:58");
INSERT INTO `grading_scales` VALUES ("81","1","12","48","52","C","Good","6","2026-07-24 23:36:58");
INSERT INTO `grading_scales` VALUES ("82","1","12","53","57","C+","Good","7","2026-07-24 23:36:58");
INSERT INTO `grading_scales` VALUES ("83","1","12","58","62","B-","Very Good","8","2026-07-24 23:36:58");
INSERT INTO `grading_scales` VALUES ("84","1","12","63","67","B","Very Good","9","2026-07-24 23:36:58");
INSERT INTO `grading_scales` VALUES ("85","1","12","68","72","B+","Exelent","10","2026-07-24 23:36:58");
INSERT INTO `grading_scales` VALUES ("86","1","12","73","77","A-","Exelent","11","2026-07-24 23:36:58");
INSERT INTO `grading_scales` VALUES ("87","1","12","78","100","A","Exelent","12","2026-07-24 23:36:58");
INSERT INTO `grading_scales` VALUES ("88","1","11","0","29","E","Fail","1","2026-07-24 23:47:03");
INSERT INTO `grading_scales` VALUES ("89","1","11","30","34","D-","Fail","2","2026-07-24 23:47:03");
INSERT INTO `grading_scales` VALUES ("90","1","11","35","39","D","Por","3","2026-07-24 23:47:03");
INSERT INTO `grading_scales` VALUES ("91","1","11","40","44","D+","Por","4","2026-07-24 23:47:03");
INSERT INTO `grading_scales` VALUES ("92","1","11","45","49","C-","Good","5","2026-07-24 23:47:03");
INSERT INTO `grading_scales` VALUES ("93","1","11","50","54","C","Good","6","2026-07-24 23:47:03");
INSERT INTO `grading_scales` VALUES ("94","1","11","55","59","C+","Good","7","2026-07-24 23:47:03");
INSERT INTO `grading_scales` VALUES ("95","1","11","60","64","B-","Very Good","8","2026-07-24 23:47:03");
INSERT INTO `grading_scales` VALUES ("96","1","11","65","69","B","Very Good","9","2026-07-24 23:47:03");
INSERT INTO `grading_scales` VALUES ("97","1","11","70","74","B+","Exelent","10","2026-07-24 23:47:03");
INSERT INTO `grading_scales` VALUES ("98","1","11","75","79","A-","Exelent","11","2026-07-24 23:47:03");
INSERT INTO `grading_scales` VALUES ("99","1","11","80","100","A","Exelent","12","2026-07-24 23:47:03");
INSERT INTO `grading_scales` VALUES ("100","1","6","0","11","E","Fail","1","2026-07-24 23:58:00");
INSERT INTO `grading_scales` VALUES ("101","1","6","12","18","D-","Fail","2","2026-07-24 23:58:00");
INSERT INTO `grading_scales` VALUES ("102","1","6","19","24","D","Poor","3","2026-07-24 23:58:00");
INSERT INTO `grading_scales` VALUES ("103","1","6","25","30","D+","Poor","4","2026-07-24 23:58:00");
INSERT INTO `grading_scales` VALUES ("104","1","6","31","36","C-","Good","5","2026-07-24 23:58:00");
INSERT INTO `grading_scales` VALUES ("105","1","6","37","42","C","Good","6","2026-07-24 23:58:00");
INSERT INTO `grading_scales` VALUES ("106","1","6","43","48","C+","Good","7","2026-07-24 23:58:00");
INSERT INTO `grading_scales` VALUES ("107","1","6","49","54","B-","Very Good","8","2026-07-24 23:58:00");
INSERT INTO `grading_scales` VALUES ("108","1","6","55","59","B","Very Good","9","2026-07-24 23:58:00");
INSERT INTO `grading_scales` VALUES ("109","1","6","60","64","B+","Exelent","10","2026-07-24 23:58:00");
INSERT INTO `grading_scales` VALUES ("110","1","6","65","69","A-","Exelent","11","2026-07-24 23:58:00");
INSERT INTO `grading_scales` VALUES ("111","1","6","70","100","A","Exelent","12","2026-07-24 23:58:00");
INSERT INTO `grading_scales` VALUES ("112","1","9","0","29","E","Fail","1","2026-07-25 00:08:55");
INSERT INTO `grading_scales` VALUES ("113","1","9","30","34","D-","Fail","2","2026-07-25 00:08:55");
INSERT INTO `grading_scales` VALUES ("114","1","9","35","39","D","Poor","3","2026-07-25 00:08:55");
INSERT INTO `grading_scales` VALUES ("115","1","9","40","44","D+","Poor","4","2026-07-25 00:08:55");
INSERT INTO `grading_scales` VALUES ("116","1","9","45","49","C-","Good","5","2026-07-25 00:08:55");
INSERT INTO `grading_scales` VALUES ("117","1","9","50","54","C","Good","6","2026-07-25 00:08:55");
INSERT INTO `grading_scales` VALUES ("118","1","9","55","59","C+","Good","7","2026-07-25 00:08:55");
INSERT INTO `grading_scales` VALUES ("119","1","9","60","64","B-","Very Good","8","2026-07-25 00:08:55");
INSERT INTO `grading_scales` VALUES ("120","1","9","65","69","B","Very Good","9","2026-07-25 00:08:55");
INSERT INTO `grading_scales` VALUES ("121","1","9","70","74","B+","Exelent","10","2026-07-25 00:08:55");
INSERT INTO `grading_scales` VALUES ("122","1","9","75","84","A-","Exelent","11","2026-07-25 00:08:55");
INSERT INTO `grading_scales` VALUES ("123","1","9","85","100","A","Exelent","12","2026-07-25 00:08:55");
INSERT INTO `grading_scales` VALUES ("124","1","5","0","15","E","Fail","1","2026-07-25 00:16:39");
INSERT INTO `grading_scales` VALUES ("125","1","5","16","20","D-","Fail","2","2026-07-25 00:16:39");
INSERT INTO `grading_scales` VALUES ("126","1","5","21","25","D","Poor","3","2026-07-25 00:16:39");
INSERT INTO `grading_scales` VALUES ("127","1","5","26","30","D+","Poor","4","2026-07-25 00:16:39");
INSERT INTO `grading_scales` VALUES ("128","1","5","31","35","C-","Good","5","2026-07-25 00:16:39");
INSERT INTO `grading_scales` VALUES ("129","1","5","36","40","C","Good","6","2026-07-25 00:16:39");
INSERT INTO `grading_scales` VALUES ("130","1","5","41","45","C+","Good","7","2026-07-25 00:16:39");
INSERT INTO `grading_scales` VALUES ("131","1","5","46","50","B-","Very Good","8","2026-07-25 00:16:39");
INSERT INTO `grading_scales` VALUES ("132","1","5","51","55","B","Very Good","9","2026-07-25 00:16:39");
INSERT INTO `grading_scales` VALUES ("133","1","5","56","60","B+","Exelent","10","2026-07-25 00:16:39");
INSERT INTO `grading_scales` VALUES ("134","1","5","61","65","A-","Exelent","11","2026-07-25 00:16:39");
INSERT INTO `grading_scales` VALUES ("135","1","5","66","100","A","Exelent","12","2026-07-25 00:16:39");
INSERT INTO `grading_scales` VALUES ("136","1","7","0","39","E","Fail","1","2026-07-25 00:29:28");
INSERT INTO `grading_scales` VALUES ("137","1","7","40","44","D-","Fail","2","2026-07-25 00:29:28");
INSERT INTO `grading_scales` VALUES ("138","1","7","45","49","D","Poor","3","2026-07-25 00:29:28");
INSERT INTO `grading_scales` VALUES ("139","1","7","50","54","D+","Poor","4","2026-07-25 00:29:28");
INSERT INTO `grading_scales` VALUES ("140","1","7","55","59","C-","Good","5","2026-07-25 00:29:28");
INSERT INTO `grading_scales` VALUES ("141","1","7","60","64","C","Good","6","2026-07-25 00:29:28");
INSERT INTO `grading_scales` VALUES ("142","1","7","65","65","C+","Good","7","2026-07-25 00:29:28");
INSERT INTO `grading_scales` VALUES ("143","1","7","70","74","B-","Very Good","8","2026-07-25 00:29:28");
INSERT INTO `grading_scales` VALUES ("144","1","7","75","79","B","Very Good","9","2026-07-25 00:29:28");
INSERT INTO `grading_scales` VALUES ("145","1","7","80","84","B+","Exelent","10","2026-07-25 00:29:28");
INSERT INTO `grading_scales` VALUES ("146","1","7","85","89","A-","Exelent","11","2026-07-25 00:29:28");
INSERT INTO `grading_scales` VALUES ("147","1","7","90","100","A","Exelent","12","2026-07-25 00:29:28");
INSERT INTO `grading_scales` VALUES ("148","1","10","0","14","E","Fail","1","2026-07-25 00:38:17");
INSERT INTO `grading_scales` VALUES ("149","1","10","15","19","D-","Fail","2","2026-07-25 00:38:17");
INSERT INTO `grading_scales` VALUES ("150","1","10","20","29","D","Poor","3","2026-07-25 00:38:17");
INSERT INTO `grading_scales` VALUES ("151","1","10","30","54","D+","Poor","4","2026-07-25 00:38:17");
INSERT INTO `grading_scales` VALUES ("152","1","10","35","39","C-","Good","5","2026-07-25 00:38:17");
INSERT INTO `grading_scales` VALUES ("153","1","10","40","44","C","Good","6","2026-07-25 00:38:17");
INSERT INTO `grading_scales` VALUES ("154","1","10","45","54","C+","Good","7","2026-07-25 00:38:17");
INSERT INTO `grading_scales` VALUES ("155","1","10","55","59","B-","Very Good","8","2026-07-25 00:38:17");
INSERT INTO `grading_scales` VALUES ("156","1","10","60","69","B","Very Good","9","2026-07-25 00:38:17");
INSERT INTO `grading_scales` VALUES ("157","1","10","70","74","B+","Exelent","10","2026-07-25 00:38:17");
INSERT INTO `grading_scales` VALUES ("158","1","10","75","79","A-","Exelent","11","2026-07-25 00:38:17");
INSERT INTO `grading_scales` VALUES ("159","1","10","80","100","A","Exelent","12","2026-07-25 00:38:17");


-- Table structure for `holidays`
DROP TABLE IF EXISTS `holidays`;
CREATE TABLE `holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `holiday_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `holiday_type` enum('public','school','religious','other') DEFAULT 'school',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_holidays_school` (`school_id`),
  KEY `idx_holidays_dates` (`start_date`,`end_date`),
  CONSTRAINT `holidays_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `holidays` VALUES ("1","1","TERM I","TEST","2026-04-04","2026-04-26","school","1","2026-07-23 23:23:39","2026-07-23 23:23:39");


-- Table structure for `incident_reports`
DROP TABLE IF EXISTS `incident_reports`;
CREATE TABLE `incident_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `incident_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `severity` varchar(20) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `incident_reports_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `incident_reports_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for `invoice_items`
DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `fee_structure_id` int(11) NOT NULL,
  `fee_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `fee_structure_id` (`fee_structure_id`),
  CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structure` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for `invoice_payments`
DROP TABLE IF EXISTS `invoice_payments`;
CREATE TABLE `invoice_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `allocated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `payment_id` (`payment_id`),
  CONSTRAINT `invoice_payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_payments_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `fee_payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `invoice_payments` VALUES ("10","3","1","10000.00","2026-07-16 11:56:01");
INSERT INTO `invoice_payments` VALUES ("11","3","26","1.00","2026-07-16 11:56:01");
INSERT INTO `invoice_payments` VALUES ("12","3","40","1.00","2026-07-16 11:56:01");
INSERT INTO `invoice_payments` VALUES ("13","3","45","1.00","2026-07-16 11:56:01");
INSERT INTO `invoice_payments` VALUES ("14","3","46","1.00","2026-07-16 11:56:01");
INSERT INTO `invoice_payments` VALUES ("15","3","47","1.00","2026-07-16 11:56:01");
INSERT INTO `invoice_payments` VALUES ("16","3","48","1.00","2026-07-16 11:56:01");
INSERT INTO `invoice_payments` VALUES ("17","3","49","1.00","2026-07-16 11:56:01");
INSERT INTO `invoice_payments` VALUES ("18","3","51","1.00","2026-07-16 11:56:01");


-- Table structure for `invoices`
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `school_id` (`school_id`),
  KEY `student_id` (`student_id`),
  KEY `class_id` (`class_id`),
  KEY `status` (`status`),
  KEY `issue_date` (`issue_date`),
  KEY `due_date` (`due_date`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `invoices` VALUES ("3","SCH-202607-0001","1","4","1","Term 1","2026","20000.00","10008.00","9992.00","partial","2026-07-16","2026-08-15","Tuition fee invoice for Term 1 2026","2026-07-16 11:56:01","2026-07-16 11:56:01");


-- Table structure for `leaveout_chits`
DROP TABLE IF EXISTS `leaveout_chits`;
CREATE TABLE `leaveout_chits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `leaveout_chits_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `leaveout_chits_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  CONSTRAINT `leaveout_chits_ibfk_3` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `leaveout_chits` VALUES ("1","1","1","4","Medical appointment","1","2026-07-08 23:55:42");
INSERT INTO `leaveout_chits` VALUES ("2","1","1","4","Medical appointment","1","2026-07-08 23:56:52");


-- Table structure for `librarian_logins`
DROP TABLE IF EXISTS `librarian_logins`;
CREATE TABLE `librarian_logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `librarian_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_librarian_id` (`librarian_id`),
  CONSTRAINT `fk_librarian_login_librarian` FOREIGN KEY (`librarian_id`) REFERENCES `librarians` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `librarian_logins` VALUES ("1","1","otienobrian029@gmail.com","$2y$10$jutIFiiFwxuf79QlgDGSv.0/OEcly6pRx/TlQc8gTxaWrDq7FnAYS","1","2026-07-05 18:22:37");


-- Table structure for `librarian_sessions`
DROP TABLE IF EXISTS `librarian_sessions`;
CREATE TABLE `librarian_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `librarian_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `idx_librarian_id` (`librarian_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_librarian_session_librarian` FOREIGN KEY (`librarian_id`) REFERENCES `librarians` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `librarian_sessions` VALUES ("2","1","b85815b05d9bf5ef7509ae940c128fef9c58871348b33f1b0059c0cf406d14e0","2026-07-06 02:02:18","2026-07-05 19:02:18");
INSERT INTO `librarian_sessions` VALUES ("3","1","1117c71b44894e86c01ac178d8b06fbc916fe900417c6d93719e4a659f3d4076","2026-07-06 02:50:37","2026-07-05 19:50:37");
INSERT INTO `librarian_sessions` VALUES ("4","1","d72ab46a73f824e5f63865a07512b6698f5a60efb27d7f9314c6bea371ed0314","2026-07-15 08:38:12","2026-07-15 01:38:12");
INSERT INTO `librarian_sessions` VALUES ("5","1","f1cfdca4e958faebbb021506fd2935f451f938b7fc31ccbd3d0b2a06abb23b06","2026-07-16 16:36:32","2026-07-16 09:36:32");
INSERT INTO `librarian_sessions` VALUES ("6","1","49feddd1ce03d2969205a501b8e1c7dfdc9e5d2a0ff6c2f49732420b103d8a85","2026-07-21 06:20:05","2026-07-20 23:20:05");
INSERT INTO `librarian_sessions` VALUES ("8","1","b983a065fc682539104a54465dc29ace236532d5999159320e55a9b4897c7974","2026-07-22 23:45:26","2026-07-22 16:45:26");
INSERT INTO `librarian_sessions` VALUES ("9","1","0b33def5e0edc697db122c32b6249d5be35bdbfa69e1123d9f2bc2c6fa7d0291","2026-07-24 08:36:12","2026-07-24 01:36:12");
INSERT INTO `librarian_sessions` VALUES ("10","1","c8c0b5d024dc9af9d87468752ea0d6e3bf88f8b65eb8b5e1d4748eb99fb3fe28","2026-07-24 09:12:08","2026-07-24 02:12:08");
INSERT INTO `librarian_sessions` VALUES ("11","1","9180e323dd7ec22a5b28fe879749b67a679ee474436c16912350cb99932fe38c","2026-07-24 22:16:41","2026-07-24 15:16:41");
INSERT INTO `librarian_sessions` VALUES ("12","1","0e119556302c021db4aa56ed1ffb851d040f01dda9a0a7c3ee8e7e48848807a9","2026-07-25 04:14:20","2026-07-24 21:14:20");
INSERT INTO `librarian_sessions` VALUES ("13","1","c7608c2ddc4d511bf86dee7e45dc86c8bbe1e6c194381c2800b489cf86958f82","2026-07-25 08:42:50","2026-07-25 01:42:50");
INSERT INTO `librarian_sessions` VALUES ("14","1","d88954d2cb7f8551bd088ede209eb24d2c70746deadc72b456ad5c82e6ad658f","2026-07-28 06:16:50","2026-07-27 23:16:50");
INSERT INTO `librarian_sessions` VALUES ("15","1","3ddab6872252344574215baea8bdf18691209f10ef75a9a878ea21d8666cb2b6","2026-08-01 21:53:50","2026-08-01 14:53:50");
INSERT INTO `librarian_sessions` VALUES ("16","1","b3a424d64a5bfa9e1321e74d70be1a841b526668175b95fb06152c46cb2e35ad","2026-08-03 01:16:36","2026-08-02 18:16:36");


-- Table structure for `librarians`
DROP TABLE IF EXISTS `librarians`;
CREATE TABLE `librarians` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_librarian_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `librarians` VALUES ("1","1","Brian","Onyango","otienobrian029@gmail.com","0745959757","active","2026-07-05 18:22:37","2026-07-05 18:24:45");


-- Table structure for `library_fines`
DROP TABLE IF EXISTS `library_fines`;
CREATE TABLE `library_fines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `parent_id` int(11) DEFAULT NULL,
  `receipt_number` varchar(255) DEFAULT NULL,
  `waiver_reason` text DEFAULT NULL,
  `waived_by` int(11) DEFAULT NULL,
  `waived_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `book_id` (`book_id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_fines_school_user` (`school_id`,`user_id`),
  KEY `idx_transaction_reference` (`transaction_reference`),
  CONSTRAINT `library_fines_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `library_fines_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `library_fines` VALUES ("28","1","4","8","4","student","125.00","62.00","partial","2026-07-24 21:27:47","2026-08-23 21:27:47","damaged","2026-08-02 18:48:41","mpesa","ws_CO_020820261848324745959757","2","UH2N81MEDX","","","");


-- Table structure for `login_attempts`
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `attempt_count` int(11) DEFAULT 1,
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp(),
  `locked_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `login_attempts` VALUES ("1","test@test.com","1","2026-07-31 10:11:59","");
INSERT INTO `login_attempts` VALUES ("2","otienobrian029@gmail.como","1","2026-07-31 12:05:41","");
INSERT INTO `login_attempts` VALUES ("3","otisbrian46@gmail.com","0","2026-07-31 15:32:52","");


-- Table structure for `parent_logins`
DROP TABLE IF EXISTS `parent_logins`;
CREATE TABLE `parent_logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_parent_id` (`parent_id`),
  CONSTRAINT `fk_parent_login_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for `parent_sessions`
DROP TABLE IF EXISTS `parent_sessions`;
CREATE TABLE `parent_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_parent_session_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `parent_sessions` VALUES ("1","2","848eaf5f4f5f0a59fea47c75a5a63343ce3f062b06e47e0ee46980784ac94f95","2026-07-05 18:11:05","2026-07-05 11:11:05");
INSERT INTO `parent_sessions` VALUES ("2","2","ab6a1861ac59d8b61cebdb816193c756fb9fbe1fbf1e91b9d128b7ab45fc1830","2026-07-05 18:16:15","2026-07-05 11:16:15");
INSERT INTO `parent_sessions` VALUES ("3","2","b12135734996309b3c697fc51fe30fa104fd5ed156bdc5766d54ef8ad284b51d","2026-07-06 02:05:52","2026-07-05 19:05:52");
INSERT INTO `parent_sessions` VALUES ("4","2","d7913fd357a06426bd3a4a36f2646661bab1253b2fdf5dbc9e399bb296b7b109","2026-07-06 03:13:06","2026-07-05 20:13:06");
INSERT INTO `parent_sessions` VALUES ("5","2","4eb4ce4f2d156a1470f39d5a250322db6ecdb18f0d22dfea42a3cedef7a5fbb7","2026-07-06 22:36:10","2026-07-06 15:36:10");
INSERT INTO `parent_sessions` VALUES ("6","2","f25376c862384d0f91c4588351be1d2499f8a6164ad6cf1abad6d948982ba81d","2026-07-07 20:41:23","2026-07-07 13:41:23");
INSERT INTO `parent_sessions` VALUES ("7","2","b5ed9e88608f6128b7e79237821dc7d495c9ec0578ca96e50c9819a912424d6f","2026-07-08 05:21:10","2026-07-07 22:21:10");
INSERT INTO `parent_sessions` VALUES ("8","2","793f95a0912104061509f702cac4731ba88625bcc67d97cbe87181d240ad42f4","2026-07-08 18:24:58","2026-07-08 11:24:58");
INSERT INTO `parent_sessions` VALUES ("9","2","6b1aa72948dcae0f742a10397795bb7ec5636e345ebb2cf854795d686224a0c6","2026-07-09 05:19:20","2026-07-08 22:19:20");
INSERT INTO `parent_sessions` VALUES ("11","2","446a4d7600eafdba54bc25e03cc152a76d1ae209253b929a4acf2ea33999dfd4","2026-07-15 07:36:11","2026-07-15 00:36:11");
INSERT INTO `parent_sessions` VALUES ("12","2","c277bca542630118d03d01f61add7b6b1ed44e0c0dce5a47720eaec70bb2da8c","2026-07-16 16:36:53","2026-07-16 09:36:53");
INSERT INTO `parent_sessions` VALUES ("13","2","76a430889e19485376b6df7ac3c1b2842ab67b64c3f528252299ce548f5ccf42","2026-07-19 23:12:26","2026-07-19 16:12:26");
INSERT INTO `parent_sessions` VALUES ("14","2","4d0b2f65ffa32817dc5ee664c7d1a250746948c5a45e00e4c3fb6fd83377cad3","2026-07-21 06:21:29","2026-07-20 23:21:29");
INSERT INTO `parent_sessions` VALUES ("15","2","5cc7b5877911f142ca20874eef5facce4ae8a87cb868db45e4aed959661f1a0d","2026-07-22 09:50:07","2026-07-22 02:50:07");
INSERT INTO `parent_sessions` VALUES ("16","2","9bd64721cc5cc666f8ac487084c9e9d3eab13231be7ba10934d4a6b0af217832","2026-07-22 14:03:39","2026-07-22 07:03:39");
INSERT INTO `parent_sessions` VALUES ("17","2","6c981d14f16c79e3e852e12fc420d7555ca30c51eacae3619501d8a856eba045","2026-07-22 23:06:38","2026-07-22 16:06:38");
INSERT INTO `parent_sessions` VALUES ("18","2","234d772b2c5133790f01f42fca46f5744dfb82c51338d9622a3c8bb7f414d075","2026-07-23 05:48:59","2026-07-22 22:48:59");
INSERT INTO `parent_sessions` VALUES ("19","2","7c0463e27e6e9537057f76a58fe89fc3fd8ac6e64a402cee5301f6ca8a6e3723","2026-07-24 02:24:07","2026-07-23 19:24:07");
INSERT INTO `parent_sessions` VALUES ("20","2","162e9b096f6dd5b855c0e45918a052c0f732917279688ab457b353421aaa0341","2026-07-24 08:25:53","2026-07-24 01:25:53");
INSERT INTO `parent_sessions` VALUES ("21","2","3361a763aaeed17adf0ebffd7d79877d50e60cc38cfeb1f7595bad2a7d884a99","2026-07-25 05:56:49","2026-07-24 22:56:49");
INSERT INTO `parent_sessions` VALUES ("22","2","a80899324ae6df3ee287e3fd1e3d16b19c83200ccbdc6edc3287557357baeb93","2026-07-26 05:01:27","2026-07-25 22:01:27");
INSERT INTO `parent_sessions` VALUES ("23","2","c26a6488fabcfe99c357b8968f8e49de57334d2c55f84bf5d92cc8835830de0c","2026-07-26 10:10:57","2026-07-26 03:10:57");
INSERT INTO `parent_sessions` VALUES ("24","2","c66e2302cc4e96c61fb47008bf2e7a4637ef843d1da8d2827071f0c90c66c7c0","2026-07-28 05:07:26","2026-07-27 22:07:26");
INSERT INTO `parent_sessions` VALUES ("25","2","c561b52ac7c48252df47461decc54a1e18ec4a874094fd957d02bc2056ea1968","2026-07-28 21:45:56","2026-07-28 14:45:56");
INSERT INTO `parent_sessions` VALUES ("26","2","76ce9bdd96c05c1f8a49eae3a8dbbc98be982cea0ef123c81fc557e1c15dd868","2026-07-29 16:47:27","2026-07-29 09:47:27");
INSERT INTO `parent_sessions` VALUES ("27","2","153549a241676c51f580cf5ccaba096c4c2725227aa78dec87e963f94a1efb87","2026-07-30 13:16:52","2026-07-30 06:16:52");
INSERT INTO `parent_sessions` VALUES ("29","2","5033538a36bd4391e108cf6fd8a2845a948712a6a35d7aa469027a6b6d69e266","2026-08-02 01:32:24","2026-08-01 18:32:24");
INSERT INTO `parent_sessions` VALUES ("30","2","38d7d20c5735aeb6cdf273ca4c5d3d65e5aeb354e5b110e44b8b3fb45d55b0ec","2026-08-02 06:15:53","2026-08-01 23:15:53");
INSERT INTO `parent_sessions` VALUES ("31","2","f79a35bf0319692858d82dedb167064a1a9b5ab4b64b9cb77e8a1aa329d386d1","2026-08-02 06:16:07","2026-08-01 23:16:07");
INSERT INTO `parent_sessions` VALUES ("32","2","db3b7962a78d1778e02c6cbbe3d2da7ad6f72b932373840f79a375ff2e40617c","2026-08-02 06:44:00","2026-08-01 23:44:00");
INSERT INTO `parent_sessions` VALUES ("33","2","232f2329dc8f23fc135b71093a29d41033024bf91e4ad7248a99b17be5eb27b7","2026-08-02 07:04:20","2026-08-02 00:04:20");
INSERT INTO `parent_sessions` VALUES ("34","2","7c32dc84de78af265890df2e1fb5a7d01cb78cc5e8779668feef251773363c62","2026-08-02 07:23:27","2026-08-02 00:23:27");
INSERT INTO `parent_sessions` VALUES ("35","2","bbba4ed6b3c35fd7a6213f7241bf02a4b9c9fbeb2eadfa39d14d909873b8c525","2026-08-02 07:26:41","2026-08-02 00:26:41");
INSERT INTO `parent_sessions` VALUES ("36","2","0399c7bb4da0b61a4e6a048115b2812545b96cfe2771db45b84eaf563182d01b","2026-08-02 07:42:22","2026-08-02 00:42:22");
INSERT INTO `parent_sessions` VALUES ("37","2","1d736bb191ead281023bd71455d0c7fed9f1ad08ebfa0451de55491ddc38a927","2026-08-02 07:56:57","2026-08-02 00:56:57");
INSERT INTO `parent_sessions` VALUES ("38","2","f1b449ce9c13dcb6e460bfbfa4446913b9d0fca0a615b4becd98526c494b9b15","2026-08-02 07:58:14","2026-08-02 00:58:14");
INSERT INTO `parent_sessions` VALUES ("39","2","d7ad9ee2e110864680eede171924c5e779058fd5a1f33b318e154f0ef288669b","2026-08-02 08:06:53","2026-08-02 01:06:53");
INSERT INTO `parent_sessions` VALUES ("40","2","ca24bb93a00f386f9307ffb0da1717bf73faeb4b60922aa58357c293c7371d90","2026-08-02 08:09:49","2026-08-02 01:09:49");
INSERT INTO `parent_sessions` VALUES ("41","2","06298dae5be956111160684d84f9db414c7ab3a858fb437a2925c452788ff3e2","2026-08-02 08:16:46","2026-08-02 01:16:46");
INSERT INTO `parent_sessions` VALUES ("42","2","5de95f75008a71119988f638bb3e94f4e7838ba2da50ba15cfea09f06e6f6ec7","2026-08-02 08:20:19","2026-08-02 01:20:19");
INSERT INTO `parent_sessions` VALUES ("43","2","0d737cb9910265825bbccf814b180e024192da3d529ec2cc8ba243c68e05f1d6","2026-08-02 08:22:57","2026-08-02 01:22:57");
INSERT INTO `parent_sessions` VALUES ("44","2","3e2631ede13503e7809b01ca55e8eae2c39ebdbb3fe223576913a40564ec66ff","2026-08-02 08:30:38","2026-08-02 01:30:38");
INSERT INTO `parent_sessions` VALUES ("45","2","2eef06b37fca995c69bf6ae7f026d13c3ae174213757d6b0d2af2d2f3a097a59","2026-08-02 08:32:15","2026-08-02 01:32:15");
INSERT INTO `parent_sessions` VALUES ("46","2","94d9aa8674a0d818aaa9f118acec5f5e2952c84d98585ab3aef27d7f82de3bf7","2026-08-02 08:34:13","2026-08-02 01:34:13");
INSERT INTO `parent_sessions` VALUES ("47","2","afdd9da8ba86c02603279fade81cd101a1ebd125997474c514e240b3dd944008","2026-08-02 08:44:01","2026-08-02 01:44:01");
INSERT INTO `parent_sessions` VALUES ("48","2","8f9b878c7d6cfd2bb190918944fe32704adfd75825fd63d2a37f76c97e5f9424","2026-08-02 08:58:50","2026-08-02 01:58:50");
INSERT INTO `parent_sessions` VALUES ("49","2","d2b32f2323df13179242c06e56baa622a0c7d980c97f86d8a7ffece4521f2012","2026-08-02 08:59:58","2026-08-02 01:59:58");
INSERT INTO `parent_sessions` VALUES ("50","2","747e732c510ab38faed7568881cdc6bd9370c4fb69ecce906fdf0e9821c585b3","2026-08-02 09:04:03","2026-08-02 02:04:03");
INSERT INTO `parent_sessions` VALUES ("51","2","85455f715de21be300f2cefbb8767aecebec7fb860fd7a11eac9ac0764903b0f","2026-08-02 09:04:42","2026-08-02 02:04:42");
INSERT INTO `parent_sessions` VALUES ("52","2","d929d41578c155cdf708582ca04b15cb63ea92bade64f809220ae7253ac6c804","2026-08-02 09:06:22","2026-08-02 02:06:22");
INSERT INTO `parent_sessions` VALUES ("53","2","09993841c5fd3705edc419171927ab142bef8db37461f5e28dc04988c5b42ebe","2026-08-02 09:15:53","2026-08-02 02:15:53");
INSERT INTO `parent_sessions` VALUES ("54","2","f4fc96c882e350a41ffd051db86da35922f7a092cdab8c3a944cf43175f22c80","2026-08-02 09:43:25","2026-08-02 02:43:25");
INSERT INTO `parent_sessions` VALUES ("55","2","5ace56f96919d07b0f8220fafa56013d960a36f7d9ee82af8364a4f4653e8622","2026-08-02 09:46:21","2026-08-02 02:46:21");
INSERT INTO `parent_sessions` VALUES ("56","2","2c1f819785b9089b97300b02c390a32822a4a0ae17e943c950dac0e477f09c37","2026-08-02 09:47:25","2026-08-02 02:47:25");
INSERT INTO `parent_sessions` VALUES ("57","2","489eb8145d29c23cbbc36af150303ccfe39c3e8d20aab27f88d405e195110b81","2026-08-02 09:48:21","2026-08-02 02:48:21");
INSERT INTO `parent_sessions` VALUES ("58","2","fc1b78eafd29afa0390db7f1748af371e520e57e5043dfee7dde415f620817eb","2026-08-02 09:50:02","2026-08-02 02:50:02");
INSERT INTO `parent_sessions` VALUES ("59","2","3048bd55849e2caa600d1f98ef69e93ecca19cbcb6e95824280155431d673dc5","2026-08-02 09:54:17","2026-08-02 02:54:17");
INSERT INTO `parent_sessions` VALUES ("60","2","48a0a0905680ee571f5f2b78d9bbf5832ff272a09f81781dc0c4f831246d3775","2026-08-02 10:39:26","2026-08-02 03:39:26");
INSERT INTO `parent_sessions` VALUES ("61","2","cb245597ba115d668458eb8e9943c2d87eef82bb5d7a71b2fad86a61cc667302","2026-08-02 11:13:16","2026-08-02 04:13:16");
INSERT INTO `parent_sessions` VALUES ("62","2","172e0bbf0f96afad3f7679e34f303effdc461dabbe05b160f8a0a10bb478d4a5","2026-08-02 11:30:40","2026-08-02 04:30:40");
INSERT INTO `parent_sessions` VALUES ("63","2","89bb0acb5f83ba5a76d1f2c29e2a0f8c7c39c5f9d40264996601a87801775f2f","2026-08-02 11:41:10","2026-08-02 04:41:10");
INSERT INTO `parent_sessions` VALUES ("64","2","0be45d9aa2ac9d2e82687ad42939ef17320cad07081ec45ab77fb59381fa92f5","2026-08-02 11:45:24","2026-08-02 04:45:24");
INSERT INTO `parent_sessions` VALUES ("65","2","5767aad1e14d03a50e087d238f7ce8028b53909a9e810e1285a26e5862cfc598","2026-08-02 11:47:41","2026-08-02 04:47:41");
INSERT INTO `parent_sessions` VALUES ("66","2","13ed901d462ddf56b54f27ddb41c24f3785758d534223305bd975b158e8757f6","2026-08-02 12:09:51","2026-08-02 05:09:51");
INSERT INTO `parent_sessions` VALUES ("67","2","38f08152e18c23b592b8f63eef72df253c9dc24b5593003774e368c71ad55fa5","2026-08-02 12:20:27","2026-08-02 05:20:27");
INSERT INTO `parent_sessions` VALUES ("68","2","28df2f2179fea6da2d7dd79635c101cf7e690fbf979b3f9f81549ec9830dea92","2026-08-02 12:22:51","2026-08-02 05:22:51");
INSERT INTO `parent_sessions` VALUES ("69","2","1287b11586050cdc56a8e21bb2df5992062cd302d34619f1b4cc93cb6fd7d848","2026-08-02 12:33:57","2026-08-02 05:33:57");
INSERT INTO `parent_sessions` VALUES ("70","2","ef667d417d262bf6fcc9bd7985d7c4211dd25b75e09cd7f3d06345247194c5b8","2026-08-02 17:47:15","2026-08-02 10:47:15");
INSERT INTO `parent_sessions` VALUES ("71","2","e282ce78a993a9a79701b42457d8f79fde883282c1e281a858168f9a24e92d79","2026-08-02 17:51:46","2026-08-02 10:51:46");
INSERT INTO `parent_sessions` VALUES ("72","2","e5360a1440c4d79c1c202b9340977d8073a85cd6bc483bbf0cd7a56c22d13d83","2026-08-02 17:53:26","2026-08-02 10:53:26");
INSERT INTO `parent_sessions` VALUES ("73","2","783e842620d99bd42ddea2198b54e09a04f2f947bc6101618d3f829438497dc6","2026-08-02 18:09:32","2026-08-02 11:09:32");
INSERT INTO `parent_sessions` VALUES ("74","2","b54aa21017969db707dadd2eedfae929d16ad8dc33498d852f15b2fe6e08a8ce","2026-08-02 18:17:54","2026-08-02 11:17:54");
INSERT INTO `parent_sessions` VALUES ("75","2","b3a159f22ad6a93126a27c32a1169b2c88da651f245272212f2a133a23250403","2026-08-02 18:18:28","2026-08-02 11:18:28");
INSERT INTO `parent_sessions` VALUES ("76","2","a7be70d96489f46eebcdcfbdc2713672ab15467aa3d3248ddfad3eff317bbb18","2026-08-02 18:41:53","2026-08-02 11:41:53");
INSERT INTO `parent_sessions` VALUES ("77","2","d9ad523d595fcaf37558628d0811a3929c409f800adf458f1eca16523a1b6222","2026-08-02 18:44:30","2026-08-02 11:44:30");
INSERT INTO `parent_sessions` VALUES ("78","2","1380803908441b07a2bdb57b6bdd0865dc88a4efdad5d68e2602cd159003d997","2026-08-02 19:08:20","2026-08-02 12:08:20");
INSERT INTO `parent_sessions` VALUES ("79","2","211e7fb3fac62ea70255e81b6b3a7624faa737fed328f7f31f4ec10dd149d105","2026-08-02 20:04:45","2026-08-02 13:04:45");
INSERT INTO `parent_sessions` VALUES ("80","2","9cf2ef4b07ac974798c7f4ee55ea0f857141adc8d36f4fcedc71dab87afb30bc","2026-08-02 21:07:44","2026-08-02 14:07:44");
INSERT INTO `parent_sessions` VALUES ("81","2","a6ce2245bc39d7fd0e31da13aed45242c550add0b23e4893db8f980131d21704","2026-08-02 22:35:40","2026-08-02 15:35:40");
INSERT INTO `parent_sessions` VALUES ("82","2","bf8e848427c4b1f8f8b04273e02c5d8b0dbe309feacb224598b22976778ee217","2026-08-02 23:01:58","2026-08-02 16:01:58");
INSERT INTO `parent_sessions` VALUES ("83","2","311e3302c33a866ed915907363469659934b86b78e874763aeca83d0bbb18ef6","2026-08-03 00:59:41","2026-08-02 17:59:41");
INSERT INTO `parent_sessions` VALUES ("84","2","b69f8b00438f06a2fd030b11a709988d498c205b59ece7461bb5bf4ad779d1a4","2026-08-03 02:32:08","2026-08-02 19:32:08");
INSERT INTO `parent_sessions` VALUES ("85","2","8d6851a8d84cf604c75691cb9c23ea731b64287532a93fd82e0aebf0b238c963","2026-08-03 04:04:30","2026-08-02 21:04:30");
INSERT INTO `parent_sessions` VALUES ("86","2","1e4052caa86839f55d66267402b8ccba4678e0d5389a9036e6d8cc4da2704c50","2026-08-03 04:20:55","2026-08-02 21:20:55");
INSERT INTO `parent_sessions` VALUES ("87","2","34108bb08bccefbb2470736f8c178692e3f9224daef1c42a3a125caf34510282","2026-08-03 05:35:59","2026-08-02 22:35:59");
INSERT INTO `parent_sessions` VALUES ("88","2","9228d87b6223f258d755bb49e850e4ddca640a93865d725d247818555e2268c0","2026-08-03 05:38:16","2026-08-02 22:38:16");
INSERT INTO `parent_sessions` VALUES ("89","2","89f4e9c04f2596458dd472b7e2a334818f596614b0ce7b1bdea8b5a1a95a53da","2026-08-03 05:39:42","2026-08-02 22:39:42");
INSERT INTO `parent_sessions` VALUES ("90","2","d76058eb051d004fa633bc459cd47cb80b4960132f5fdd059ad321a8a70b1b6d","2026-08-03 05:41:04","2026-08-02 22:41:04");


-- Table structure for `parents`
DROP TABLE IF EXISTS `parents`;
CREATE TABLE `parents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `relationship` enum('Father','Mother','Guardian') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_email` (`email`),
  CONSTRAINT `fk_parents_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `parents` VALUES ("2","1","SAMUEL","OKECH","otienobrian029@gmail.com","0745959757","40718992","Kisumu\n40100 kisumu","Father","2026-07-04 18:52:29");


-- Table structure for `password_resets`
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_email` (`email`),
  KEY `idx_token` (`token`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `password_resets` VALUES ("1","otienobrian029@gmail.com","afab3b479fc0e4ca6acc6951c82ddc35c8604ccdcb87f2f2ea797d51db9b04814a7dab2288752f31276ec396b6855a7eb022","2025-12-18 20:05:32","2025-12-18 21:05:32");


-- Table structure for `reminder_history`
DROP TABLE IF EXISTS `reminder_history`;
CREATE TABLE `reminder_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `term` varchar(50) NOT NULL,
  `outstanding_amount` decimal(10,2) NOT NULL,
  `reminder_type` enum('email','letter','manual') NOT NULL DEFAULT 'email',
  `message` text DEFAULT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_school_year` (`school_id`,`year`),
  KEY `idx_student` (`student_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `reminder_history_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reminder_history_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `reminder_history` VALUES ("1","4","1","2026","Term 1","9983.00","email","testing email notification","failed","2026-07-20 03:08:30","2026-07-20 03:08:30");
INSERT INTO `reminder_history` VALUES ("2","4","1","2026","Term 1","9983.00","email","testing email notification","failed","2026-07-20 03:13:42","2026-07-20 03:13:42");
INSERT INTO `reminder_history` VALUES ("3","4","1","2026","Term 1","9983.00","email","test","failed","2026-07-20 03:14:02","2026-07-20 03:14:02");
INSERT INTO `reminder_history` VALUES ("4","4","1","2026","Term 1","9983.00","email","test","sent","2026-07-20 03:16:12","2026-07-20 03:16:12");
INSERT INTO `reminder_history` VALUES ("5","4","1","2026","Term 1","9983.00","email","that is fee reminder","sent","2026-07-20 03:23:30","2026-07-20 03:23:30");
INSERT INTO `reminder_history` VALUES ("6","4","1","2026","Term 1","9983.00","email","","sent","2026-07-20 03:25:18","2026-07-20 03:25:18");
INSERT INTO `reminder_history` VALUES ("7","4","1","2026","Term 1","9983.00","email","","sent","2026-07-20 03:25:36","2026-07-20 03:25:36");
INSERT INTO `reminder_history` VALUES ("8","4","1","2026","Term 1","9983.00","email","","sent","2026-07-20 03:29:39","2026-07-20 03:29:39");
INSERT INTO `reminder_history` VALUES ("9","4","1","2026","Term 1","9983.00","email","","sent","2026-07-20 03:29:43","2026-07-20 03:29:43");
INSERT INTO `reminder_history` VALUES ("10","4","1","2026","Term 1","9983.00","email","","sent","2026-07-20 03:29:48","2026-07-20 03:29:48");
INSERT INTO `reminder_history` VALUES ("11","4","1","2026","Term 1","9983.00","email","","sent","2026-07-20 03:31:23","2026-07-20 03:31:23");
INSERT INTO `reminder_history` VALUES ("12","4","1","2026","Term 1","9983.00","email","","sent","2026-07-20 03:31:27","2026-07-20 03:31:27");
INSERT INTO `reminder_history` VALUES ("13","4","1","2026","Term 1","9983.00","email","","sent","2026-07-20 03:31:33","2026-07-20 03:31:33");
INSERT INTO `reminder_history` VALUES ("14","4","1","2026","Term 1","10981.00","email","","sent","2026-07-24 01:39:05","2026-07-24 01:39:05");


-- Table structure for `resources`
DROP TABLE IF EXISTS `resources`;
CREATE TABLE `resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `downloads` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_hash` varchar(32) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_file_hash` (`file_hash`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `resources` VALUES ("20","test","Secondary","Electrical principles","PDF","hbn jbnkm.,;\'m,klm","69b029291de4f_LOOPTORRENT.docx","8","2026-03-10 17:22:33","1be5e82cbf76368955f5dec380630c44","1");
INSERT INTO `resources` VALUES ("21","jhvnm","Primary","Electrical principles","PDF","jbkm m, m,","api/uploads/69b02ae8cc597_KENAS CONSTRUCTION AND RENOVATION LTD.docx","3","2026-03-10 17:30:00","f8b1944b7de9189b0d4081c0d7cfee46","3");
INSERT INTO `resources` VALUES ("22","poj0jiojpoi","Primary","Electrical principles","PDF"," , ,  j n  m, ,knknklnklnkl","api/uploads/69b02cd108a90_https.docx","4","2026-03-10 17:38:09","140803be555067a953debe486ceae1b1","1");
INSERT INTO `resources` VALUES ("23","test 2","Primary","Electrical principles","DOC","nm nm, m,. ,.nkl ,.","api/uploads/69b02da23d284_PROJECT.docx","6","2026-03-10 17:41:38","a0499cd64ba631411e8122a7d859ff20","3");
INSERT INTO `resources` VALUES ("24","test 3","secondary","Electrical principles","pdf","klnjkrvjjkb , jk","api/uploads/69b02e8d4925f_Brian Onyango 2.docx","9","2026-03-10 17:45:33","af5ab37ef45a68afc078fc26c9d86320","1");
INSERT INTO `resources` VALUES ("25","DOMESTIC WATER SUPLY","College","Solar installation","PDF","this is domestic water  suply notes pdf","api/uploads/69b17e3fcad9e_DOMESTIC WATER SYPPLLY  I     LEARNING OUTLINE.pdf","44","2026-03-11 17:37:51","d6bb6412e21d0f055cb63343119c6de7","3");
INSERT INTO `resources` VALUES ("26","TEST 4","Primary","Electrical principles","PDF",";HIOE;CKLCN,/C","api/uploads/69b17fa0b3a29_p5.pdf","14","2026-03-11 17:43:44","5cda01173befa3a6e23ea23e7d52a4ac","1");
INSERT INTO `resources` VALUES ("27","TEST 5","College","Electrical principles","pdf","IHJKNJCNWLKNLNC","api/uploads/69b181d343036_LEARNING-GUIDE-FOR-BASIC-COMPETENCIES-LEVEL-6 (1).pdf","30","2026-03-11 17:53:07","be3f5070e42052bfa63b3b4a9ea171da","3");
INSERT INTO `resources` VALUES ("28","TEST6","College","Electrical principles","PDF","KGV,JN NMNKLIYHGFUCJHNM/UIG","api/uploads/69b572d05757b_downloaded_1760873391530.pdf","15","2026-03-14 17:38:08","d41d8cd98f00b204e9800998ecf8427e","1");
INSERT INTO `resources` VALUES ("29","TEST 7","Primary","Electrical principles","PDF","YCVKJKCNJKHUIVKHFNKLNVWKLV","api/uploads/69b5ac121c0a2_downloaded_1765453715140.pdf","6","2026-03-14 21:42:26","61725cf198f2491fdb1abcc7fc90d58a","3");
INSERT INTO `resources` VALUES ("31","EUIFEJIFBNEJKV","Primary","Electrical principles","PDF","JINJKL JBJIKNMLN","api/uploads/69ba510e7d103_EXTRACTED.pdf","5","2026-03-18 10:15:26","6d34e8b2f851bfaf3a17126862463951","1");
INSERT INTO `resources` VALUES ("47","7I8C TTVUI GUIOGGIO","Primary","Electrical principles","PDF","TETUFYFIU","api/uploads/6a6b3db1cc796_invoices_report_2026-07-28.pdf","1","2026-07-30 15:04:01","06c5f4bdcd7e2518b8ff03432025bfa0","1");


-- Table structure for `school_b2c_responses`
DROP TABLE IF EXISTS `school_b2c_responses`;
CREATE TABLE `school_b2c_responses` (
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
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `school_b2c_responses` VALUES ("1","6","result","2040","Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .","c6fe-4c50-848a-aeda3881d02211","AG_20260709_0100103718mikj6ae3yu","UG90000000","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":2040,\"ResultDesc\":\"Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .\",\"OriginatorConversationID\":\"c6fe-4c50-848a-aeda3881d02211\",\"ConversationID\":\"AG_20260709_0100103718mikj6ae3yu\",\"TransactionID\":\"UG90000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-09 20:49:43");
INSERT INTO `school_b2c_responses` VALUES ("2","7","result","2040","Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .","458f-4c59-86c9-6b9348359b4328597","AG_20260712_010010030xlsx3qv45vq","UGC0000000","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":2040,\"ResultDesc\":\"Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4328597\",\"ConversationID\":\"AG_20260712_010010030xlsx3qv45vq\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 15:41:18");
INSERT INTO `school_b2c_responses` VALUES ("3","8","result","2040","Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .","458f-4c59-86c9-6b9348359b4328612","AG_20260712_010010060xm61xrobdyn","UGC0000000","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":2040,\"ResultDesc\":\"Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4328612\",\"ConversationID\":\"AG_20260712_010010060xm61xrobdyn\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 15:41:35");
INSERT INTO `school_b2c_responses` VALUES ("4","9","result","2040","Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .","458f-4c59-86c9-6b9348359b4328842","AG_20260712_010010370xszfoqvkj18","UGC0000000","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":2040,\"ResultDesc\":\"Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4328842\",\"ConversationID\":\"AG_20260712_010010370xszfoqvkj18\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 15:46:53");
INSERT INTO `school_b2c_responses` VALUES ("5","10","result","7","The ReceiverParty information is invalid.","4311-46f6-9a91-011b31669b70152307","AG_20260712_010010090xvdtwhb5m15","UGC0907OD3","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":7,\"ResultDesc\":\"The ReceiverParty information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70152307\",\"ConversationID\":\"AG_20260712_010010090xvdtwhb5m15\",\"TransactionID\":\"UGC0907OD3\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 15:48:45");
INSERT INTO `school_b2c_responses` VALUES ("6","11","result","7","The ReceiverParty information is invalid.","4311-46f6-9a91-011b31669b70152526","AG_20260712_010010330y03yhoy6ul6","UGC0X03E5X","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":7,\"ResultDesc\":\"The ReceiverParty information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70152526\",\"ConversationID\":\"AG_20260712_010010330y03yhoy6ul6\",\"TransactionID\":\"UGC0X03E5X\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 15:52:25");
INSERT INTO `school_b2c_responses` VALUES ("7","12","result","7","The ReceiverParty information is invalid.","4311-46f6-9a91-011b31669b70152603","AG_20260712_010010090y2r36wms494","UGC0907OD4","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":7,\"ResultDesc\":\"The ReceiverParty information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70152603\",\"ConversationID\":\"AG_20260712_010010090y2r36wms494\",\"TransactionID\":\"UGC0907OD4\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 15:54:29");
INSERT INTO `school_b2c_responses` VALUES ("8","13","result","7","The ReceiverParty information is invalid.","4311-46f6-9a91-011b31669b70152742","AG_20260712_010010150y5knhmysqvh","UGC0F06E05","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":7,\"ResultDesc\":\"The ReceiverParty information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70152742\",\"ConversationID\":\"AG_20260712_010010150y5knhmysqvh\",\"TransactionID\":\"UGC0F06E05\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 15:56:40");
INSERT INTO `school_b2c_responses` VALUES ("9","14","result","2001","The initiator information is invalid.","458f-4c59-86c9-6b9348359b4329651","AG_20260712_010010030ye6s6aobu6c","UGC030DL5G","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4329651\",\"ConversationID\":\"AG_20260712_010010030ye6s6aobu6c\",\"TransactionID\":\"UGC030DL5G\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 16:03:23");
INSERT INTO `school_b2c_responses` VALUES ("10","15","result","2001","The initiator information is invalid.","4311-46f6-9a91-011b31669b70153288","AG_20260712_010010030yga79fmuqwz","UGC030DL5H","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70153288\",\"ConversationID\":\"AG_20260712_010010030yga79fmuqwz\",\"TransactionID\":\"UGC030DL5H\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 16:05:00");
INSERT INTO `school_b2c_responses` VALUES ("11","16","result","2001","The initiator information is invalid.","4311-46f6-9a91-011b31669b70154141","AG_20260712_010010030yxlg6myxi2v","UGC030DJK7","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70154141\",\"ConversationID\":\"AG_20260712_010010030yxlg6myxi2v\",\"TransactionID\":\"UGC030DJK7\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 16:18:28");
INSERT INTO `school_b2c_responses` VALUES ("12","19","result","2001","The initiator information is invalid.","4311-46f6-9a91-011b31669b70154453","AG_20260712_010010030z43tx1qrrp1","UGC030DJK8","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70154453\",\"ConversationID\":\"AG_20260712_010010030z43tx1qrrp1\",\"TransactionID\":\"UGC030DJK8\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 16:23:35");
INSERT INTO `school_b2c_responses` VALUES ("13","20","result","2001","The initiator information is invalid.","458f-4c59-86c9-6b9348359b4330592","AG_20260712_010010030z63xyu21r6b","UGC030DJK9","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4330592\",\"ConversationID\":\"AG_20260712_010010030z63xyu21r6b\",\"TransactionID\":\"UGC030DJK9\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 16:25:05");
INSERT INTO `school_b2c_responses` VALUES ("14","21","result","8006","The security credential is locked.","458f-4c59-86c9-6b9348359b4330735","AG_20260712_010010030z9eubmye52b","UGC030DJKA","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4330735\",\"ConversationID\":\"AG_20260712_010010030z9eubmye52b\",\"TransactionID\":\"UGC030DJKA\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 16:27:39");
INSERT INTO `school_b2c_responses` VALUES ("15","22","result","8006","The security credential is locked.","4311-46f6-9a91-011b31669b70155080","AG_20260712_010010030zftpfo3ay3f","UGC030DJKC","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70155080\",\"ConversationID\":\"AG_20260712_010010030zftpfo3ay3f\",\"TransactionID\":\"UGC030DJKC\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 16:32:39");
INSERT INTO `school_b2c_responses` VALUES ("16","23","result","8006","The security credential is locked.","4311-46f6-9a91-011b31669b70155176","AG_20260712_010010030zhagfgjtg5s","UGC030DJKD","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70155176\",\"ConversationID\":\"AG_20260712_010010030zhagfgjtg5s\",\"TransactionID\":\"UGC030DJKD\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 16:33:47");
INSERT INTO `school_b2c_responses` VALUES ("17","24","result","8006","The security credential is locked.","4311-46f6-9a91-011b31669b70156464","AG_20260712_0100100310a3jz8p29s1","UGC030DJKE","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70156464\",\"ConversationID\":\"AG_20260712_0100100310a3jz8p29s1\",\"TransactionID\":\"UGC030DJKE\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 16:56:11");
INSERT INTO `school_b2c_responses` VALUES ("18","25","result","8006","The security credential is locked.","458f-4c59-86c9-6b9348359b4332860","AG_20260712_0100100310g908rz6htr","UGC030DJKH","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4332860\",\"ConversationID\":\"AG_20260712_0100100310g908rz6htr\",\"TransactionID\":\"UGC030DJKH\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 17:00:58");
INSERT INTO `school_b2c_responses` VALUES ("19","26","result","8006","The security credential is locked.","4311-46f6-9a91-011b31669b70156977","AG_20260712_0100100310kmn2uz3j35","UGC030DL5N","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70156977\",\"ConversationID\":\"AG_20260712_0100100310kmn2uz3j35\",\"TransactionID\":\"UGC030DL5N\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 17:04:22");
INSERT INTO `school_b2c_responses` VALUES ("20","27","result","8006","The security credential is locked.","4311-46f6-9a91-011b31669b70158461","AG_20260712_0100100311e3sib7qumz","UGC030DL5O","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70158461\",\"ConversationID\":\"AG_20260712_0100100311e3sib7qumz\",\"TransactionID\":\"UGC030DL5O\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 17:27:17");
INSERT INTO `school_b2c_responses` VALUES ("21","28","result","8006","The security credential is locked.","458f-4c59-86c9-6b9348359b4335394","AG_20260712_0100100312b8x6r03cbf","UGC030DL5Q","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4335394\",\"ConversationID\":\"AG_20260712_0100100312b8x6r03cbf\",\"TransactionID\":\"UGC030DL5Q\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 17:53:06");
INSERT INTO `school_b2c_responses` VALUES ("22","29","result","8006","The security credential is locked.","4311-46f6-9a91-011b31669b70161381","AG_20260712_010010031321bsgc5y61","UGC030DJKP","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70161381\",\"ConversationID\":\"AG_20260712_010010031321bsgc5y61\",\"TransactionID\":\"UGC030DJKP\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 18:13:54");
INSERT INTO `school_b2c_responses` VALUES ("23","30","result","8006","The security credential is locked.","4311-46f6-9a91-011b31669b70162365","AG_20260712_0100100313mtebcmhlh4","UGC030DL5V","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70162365\",\"ConversationID\":\"AG_20260712_0100100313mtebcmhlh4\",\"TransactionID\":\"UGC030DL5V\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 18:30:03");
INSERT INTO `school_b2c_responses` VALUES ("24","31","result","4001","Insufficient balance","4311-46f6-9a91-011b31669b70162534","AG_20260712_0100100313rinqkn2x41","UGC0000000","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70162534\",\"ConversationID\":\"AG_20260712_0100100313rinqkn2x41\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 18:33:42");
INSERT INTO `school_b2c_responses` VALUES ("25","32","result","4001","Insufficient balance","4311-46f6-9a91-011b31669b70162580","AG_20260712_0100100313sdyhs8jx90","UGC0000000","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70162580\",\"ConversationID\":\"AG_20260712_0100100313sdyhs8jx90\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 18:34:24");
INSERT INTO `school_b2c_responses` VALUES ("26","33","result","4001","Insufficient balance","458f-4c59-86c9-6b9348359b4337854","AG_20260712_0100100314k229w74l1e","UGC0000000","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4337854\",\"ConversationID\":\"AG_20260712_0100100314k229w74l1e\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 18:55:56");
INSERT INTO `school_b2c_responses` VALUES ("27","34","result","4001","Insufficient balance","4311-46f6-9a91-011b31669b70163860","AG_20260712_0100100314kykha6f5zm","UGC0000000","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70163860\",\"ConversationID\":\"AG_20260712_0100100314kykha6f5zm\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 18:56:36");
INSERT INTO `school_b2c_responses` VALUES ("28","37","result","4001","Insufficient balance","4311-46f6-9a91-011b31669b70166588","AG_20260712_0100100316s3pshk8cxg","UGC0000000","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70166588\",\"ConversationID\":\"AG_20260712_0100100316s3pshk8cxg\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 19:58:08");
INSERT INTO `school_b2c_responses` VALUES ("29","38","result","4001","Insufficient balance","4311-46f6-9a91-011b31669b70166855","AG_20260712_0100100316ziy0r7g334","UGC0000000","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70166855\",\"ConversationID\":\"AG_20260712_0100100316ziy0r7g334\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-12 20:03:55");
INSERT INTO `school_b2c_responses` VALUES ("30","40","result","4001","Insufficient balance","ff3e-4fa4-abc0-8eb3aa92c0d9110087","AG_20260715_0100100301s7pngznnhy","UGF0000000","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"ff3e-4fa4-abc0-8eb3aa92c0d9110087\",\"ConversationID\":\"AG_20260715_0100100301s7pngznnhy\",\"TransactionID\":\"UGF0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-15 00:50:30");
INSERT INTO `school_b2c_responses` VALUES ("31","41","result","4001","Insufficient balance","3a62-4214-aa55-1c05a6d85a1c29486","AG_20260715_0100100302ighd81jdj9","UGF0000000","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"3a62-4214-aa55-1c05a6d85a1c29486\",\"ConversationID\":\"AG_20260715_0100100302ighd81jdj9\",\"TransactionID\":\"UGF0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-15 01:10:54");
INSERT INTO `school_b2c_responses` VALUES ("54","","result","","","","","","","","","","2026-07-19 23:40:11");
INSERT INTO `school_b2c_responses` VALUES ("55","62","result","4001","Insufficient balance","7cd0-4a28-8d04-cb27fe4732c675739","AG_20260720_0100100303magp3v5e6s","UGK0000000","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"7cd0-4a28-8d04-cb27fe4732c675739\",\"ConversationID\":\"AG_20260720_0100100303magp3v5e6s\",\"TransactionID\":\"UGK0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-20 01:41:59");
INSERT INTO `school_b2c_responses` VALUES ("56","63","result","4001","Insufficient balance","6839-428d-8589-2bcb00296f3788158","AG_20260720_010010030ay9icuyg8xg","UGK0000000","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"6839-428d-8589-2bcb00296f3788158\",\"ConversationID\":\"AG_20260720_010010030ay9icuyg8xg\",\"TransactionID\":\"UGK0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-20 05:07:15");
INSERT INTO `school_b2c_responses` VALUES ("57","64","result","4001","Insufficient balance","6839-428d-8589-2bcb00296f3788315","AG_20260720_010010030b29th2u5c1g","UGK0000000","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"6839-428d-8589-2bcb00296f3788315\",\"ConversationID\":\"AG_20260720_010010030b29th2u5c1g\",\"TransactionID\":\"UGK0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-20 05:10:22");
INSERT INTO `school_b2c_responses` VALUES ("58","76","result","0","The service request is processed successfully.","6e47-4967-bcb9-74445bede7d2149876","AG_20260725_0100100306hfuhjt5vaj","UGP030DS9I","10.00","254708374149 - John Doe","25.07.2026 03:01:31","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2149876\",\"ConversationID\":\"AG_20260725_0100100306hfuhjt5vaj\",\"TransactionID\":\"UGP030DS9I\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DS9I\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:01:31\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020629.44},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 03:02:17");
INSERT INTO `school_b2c_responses` VALUES ("59","77","result","0","The service request is processed successfully.","6e47-4967-bcb9-74445bede7d2149944","AG_20260725_0100100306isnutdzh5z","UGP030DTRC","10.00","254708374149 - John Doe","25.07.2026 03:02:35","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2149944\",\"ConversationID\":\"AG_20260725_0100100306isnutdzh5z\",\"TransactionID\":\"UGP030DTRC\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRC\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:02:35\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020585.84},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 03:03:20");
INSERT INTO `school_b2c_responses` VALUES ("60","78","result","0","The service request is processed successfully.","b13d-4e1d-8fb5-0f0d66c4323015398","AG_20260725_0100100306lgkp3uxieq","UGP030DS9J","10.00","254708374149 - John Doe","25.07.2026 03:04:39","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"b13d-4e1d-8fb5-0f0d66c4323015398\",\"ConversationID\":\"AG_20260725_0100100306lgkp3uxieq\",\"TransactionID\":\"UGP030DS9J\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DS9J\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:04:39\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020542.24},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 03:05:24");
INSERT INTO `school_b2c_responses` VALUES ("61","79","result","0","The service request is processed successfully.","6e47-4967-bcb9-74445bede7d2150262","AG_20260725_0100100306pdhg6hp4zb","UGP030DTRD","10.00","254708374149 - John Doe","25.07.2026 03:07:41","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2150262\",\"ConversationID\":\"AG_20260725_0100100306pdhg6hp4zb\",\"TransactionID\":\"UGP030DTRD\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRD\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:07:41\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020498.64},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 03:08:27");
INSERT INTO `school_b2c_responses` VALUES ("62","80","result","0","The service request is processed successfully.","b13d-4e1d-8fb5-0f0d66c4323015602","AG_20260725_0100100306smeff2m19e","UGP030DS9L","10.00","254708374149 - John Doe","25.07.2026 03:10:13","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"b13d-4e1d-8fb5-0f0d66c4323015602\",\"ConversationID\":\"AG_20260725_0100100306smeff2m19e\",\"TransactionID\":\"UGP030DS9L\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DS9L\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:10:13\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020455.04},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 03:10:59");
INSERT INTO `school_b2c_responses` VALUES ("63","81","result","0","The service request is processed successfully.","b13d-4e1d-8fb5-0f0d66c4323015718","AG_20260725_0100100306vs08jfd9dp","UGP030DTRE","10.00","254708374149 - John Doe","25.07.2026 03:12:40","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"b13d-4e1d-8fb5-0f0d66c4323015718\",\"ConversationID\":\"AG_20260725_0100100306vs08jfd9dp\",\"TransactionID\":\"UGP030DTRE\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRE\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:12:40\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020411.44},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 03:13:26");
INSERT INTO `school_b2c_responses` VALUES ("64","82","result","0","The service request is processed successfully.","6e47-4967-bcb9-74445bede7d2150843","AG_20260725_01001003072oz2xm4nf9","UGP030DS9N","10.00","254708374149 - John Doe","25.07.2026 03:18:03","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2150843\",\"ConversationID\":\"AG_20260725_01001003072oz2xm4nf9\",\"TransactionID\":\"UGP030DS9N\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DS9N\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:18:03\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020367.84},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 03:18:48");
INSERT INTO `school_b2c_responses` VALUES ("65","83","result","0","The service request is processed successfully.","b13d-4e1d-8fb5-0f0d66c4323016055","AG_20260725_01001003077xj8a055yi","UGP030DTRI","10.00","254708374149 - John Doe","25.07.2026 03:22:07","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"b13d-4e1d-8fb5-0f0d66c4323016055\",\"ConversationID\":\"AG_20260725_01001003077xj8a055yi\",\"TransactionID\":\"UGP030DTRI\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRI\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:22:07\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020324.24},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 03:22:53");
INSERT INTO `school_b2c_responses` VALUES ("66","84","result","0","The service request is processed successfully.","6e47-4967-bcb9-74445bede7d2151316","AG_20260725_0100100307cpjvnuq7c3","UGP030DTRJ","10.00","254708374149 - John Doe","25.07.2026 03:25:50","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2151316\",\"ConversationID\":\"AG_20260725_0100100307cpjvnuq7c3\",\"TransactionID\":\"UGP030DTRJ\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRJ\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:25:50\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020280.64},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 03:26:36");
INSERT INTO `school_b2c_responses` VALUES ("67","85","result","0","The service request is processed successfully.","b13d-4e1d-8fb5-0f0d66c4323017549","AG_20260725_0100100308kgk5tgbi2e","UGP030DS9S","10.00","254708374149 - John Doe","25.07.2026 03:59:51","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"b13d-4e1d-8fb5-0f0d66c4323017549\",\"ConversationID\":\"AG_20260725_0100100308kgk5tgbi2e\",\"TransactionID\":\"UGP030DS9S\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DS9S\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:59:51\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020237.04},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 04:00:37");
INSERT INTO `school_b2c_responses` VALUES ("68","","result","","","","","","","","","","2026-07-27 22:14:30");
INSERT INTO `school_b2c_responses` VALUES ("69","","result","","","","","","","","","","2026-07-27 22:38:36");
INSERT INTO `school_b2c_responses` VALUES ("70","102","result","0","The service request is processed successfully.","8544-4e2a-bd68-bbd981b518db52627","AG_20260727_010010031d2dx77ekv48","TEST1785182157","10.00","254708374149 - Test","27.07.2026 21:55:57","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"8544-4e2a-bd68-bbd981b518db52627\",\"ConversationID\":\"AG_20260727_010010031d2dx77ekv48\",\"TransactionID\":\"TEST1785182157\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":\"10.00\"},{\"Key\":\"TransactionReceipt\",\"Value\":\"TEST1785182157\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - Test\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"27.07.2026 21:55:57\"}]}}}","2026-07-27 22:55:57");
INSERT INTO `school_b2c_responses` VALUES ("71","","result","","","","","","","","","","2026-07-27 23:29:10");
INSERT INTO `school_b2c_responses` VALUES ("72","","result","","","","","","","","","","2026-07-27 23:29:46");
INSERT INTO `school_b2c_responses` VALUES ("73","","result","","","","","","","","","{\"Body\":{\"stkCallback\":{\"MerchantRequestID\":\"test-merchant\",\"CheckoutRequestID\":\"test-checkout\",\"ResultCode\":0,\"ResultDesc\":\"Test successful callback\",\"CallbackMetadata\":{\"Item\":[{\"Name\":\"Amount\",\"Value\":10},{\"Name\":\"MpesaReceiptNumber\",\"Value\":\"TEST123\"},{\"Name\":\"TransactionDate\",\"Value\":20260728145300},{\"Name\":\"PhoneNumber\",\"Value\":254708374149}]}}}}","2026-07-28 14:53:46");
INSERT INTO `school_b2c_responses` VALUES ("74","","result","","","","","","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"Success\",\"OriginatorConversationID\":\"test-originator\",\"ConversationID\":\"test-conversation\",\"TransactionID\":\"TST12345678\",\"ReceiptNumber\":\"TEST123\",\"TransactionDate\":\"20260728145500\",\"Amount\":10,\"B2CRecipientIsRegisteredCustomer\":\"Y\",\"B2CChargesPaidAccount\":\"MSISDN\",\"TransactionReason\":\"SalaryPayment\",\"DebitAccountChargedAmount\":10.5,\"DebitAccountCharged\":600997,\"DebitAccountReference\":\"test-ref\",\"KESAccountBalance\":1000,\"UtilityAccountBalance\":0,\"B2CRecipientAccount\":\"254708374149\",\"B2CUtilityAccountAvailableBalance\":0,\"B2CWorkingAccountAvailableBalance\":0},\"OriginatorConversationID\":\"test-originator\",\"ConversationID\":\"test-conversation\",\"ResponseCode\":\"0,\"ResponseDescription\":\"Success\"}","2026-07-28 14:56:09");
INSERT INTO `school_b2c_responses` VALUES ("75","","result","0","Success","b8a0-4b73-8c6c-70a18412423217867","AG_20260728_010010030vtjne68gfs6","TST12345678","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"Success\",\"OriginatorConversationID\":\"b8a0-4b73-8c6c-70a18412423217867\",\"ConversationID\":\"AG_20260728_010010030vtjne68gfs6\",\"TransactionID\":\"TST12345678\",\"ReceiptNumber\":\"TEST123\",\"TransactionDate\":\"20260728145500\",\"Amount\":10,\"B2CRecipientIsRegisteredCustomer\":\"Y\",\"B2CChargesPaidAccount\":\"MSISDN\",\"TransactionReason\":\"SalaryPayment\",\"DebitAccountChargedAmount\":10.5,\"DebitAccountCharged\":600997,\"DebitAccountReference\":\"test-ref\",\"KESAccountBalance\":1000,\"UtilityAccountBalance\":0,\"B2CRecipientAccount\":\"254708374149\",\"B2CUtilityAccountAvailableBalance\":0,\"B2CWorkingAccountAvailableBalance\":0},\"OriginatorConversationID\":\"b8a0-4b73-8c6c-70a18412423217867\",\"ConversationID\":\"AG_20260728_010010030vtjne68gfs6\",\"ResponseCode\":\"0\",\"ResponseDescription\":\"Success\"}","2026-07-28 14:59:04");
INSERT INTO `school_b2c_responses` VALUES ("76","123","result","0","Success","b8a0-4b73-8c6c-70a18412423218383","AG_20260728_010010030w5xf04rpc5q","TST12345678","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"Success\",\"OriginatorConversationID\":\"b8a0-4b73-8c6c-70a18412423218383\",\"ConversationID\":\"AG_20260728_010010030w5xf04rpc5q\",\"TransactionID\":\"TST12345678\",\"ReceiptNumber\":\"TEST123\",\"TransactionDate\":\"20260728150100\",\"Amount\":10,\"B2CRecipientIsRegisteredCustomer\":\"Y\",\"B2CChargesPaidAccount\":\"MSISDN\",\"TransactionReason\":\"SalaryPayment\",\"DebitAccountChargedAmount\":10.5,\"DebitAccountCharged\":600997,\"DebitAccountReference\":\"test-ref\",\"KESAccountBalance\":1000,\"UtilityAccountBalance\":0,\"B2CRecipientAccount\":\"254708374149\",\"B2CUtilityAccountAvailableBalance\":0,\"B2CWorkingAccountAvailableBalance\":0},\"OriginatorConversationID\":\"b8a0-4b73-8c6c-70a18412423218383\",\"ConversationID\":\"AG_20260728_010010030w5xf04rpc5q\",\"ResponseCode\":\"0\",\"ResponseDescription\":\"Success\"}","2026-07-28 15:02:48");
INSERT INTO `school_b2c_responses` VALUES ("77","124","result","0","Success","a399-4d4a-b8cf-cb102e9a964a31837","AG_20260728_010010030wi295nnsg88","TST12345678","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"Success\",\"OriginatorConversationID\":\"a399-4d4a-b8cf-cb102e9a964a31837\",\"ConversationID\":\"AG_20260728_010010030wi295nnsg88\",\"TransactionID\":\"TST12345678\",\"ReceiptNumber\":\"TEST123\",\"TransactionDate\":\"20260728151000\",\"Amount\":10,\"B2CRecipientIsRegisteredCustomer\":\"Y\",\"B2CChargesPaidAccount\":\"MSISDN\",\"TransactionReason\":\"SalaryPayment\",\"DebitAccountChargedAmount\":10.5,\"DebitAccountCharged\":600997,\"DebitAccountReference\":\"test-ref\",\"KESAccountBalance\":1000,\"UtilityAccountBalance\":0,\"B2CRecipientAccount\":\"254708374149\",\"B2CUtilityAccountAvailableBalance\":0,\"B2CWorkingAccountAvailableBalance\":0},\"OriginatorConversationID\":\"a399-4d4a-b8cf-cb102e9a964a31837\",\"ConversationID\":\"AG_20260728_010010030wi295nnsg88\",\"ResponseCode\":\"0\",\"ResponseDescription\":\"Success\"}","2026-07-28 15:11:12");
INSERT INTO `school_b2c_responses` VALUES ("78","124","result","0","Success","a399-4d4a-b8cf-cb102e9a964a31837","AG_20260728_010010030wi295nnsg88","TST12345678","","","","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"Success\",\"OriginatorConversationID\":\"a399-4d4a-b8cf-cb102e9a964a31837\",\"ConversationID\":\"AG_20260728_010010030wi295nnsg88\",\"TransactionID\":\"TST12345678\",\"ReceiptNumber\":\"TEST123\",\"TransactionDate\":\"20260728151000\",\"Amount\":10,\"B2CRecipientIsRegisteredCustomer\":\"Y\",\"B2CChargesPaidAccount\":\"MSISDN\",\"TransactionReason\":\"SalaryPayment\",\"DebitAccountChargedAmount\":10.5,\"DebitAccountCharged\":600997,\"DebitAccountReference\":\"test-ref\",\"KESAccountBalance\":1000,\"UtilityAccountBalance\":0,\"B2CRecipientAccount\":\"254708374149\",\"B2CUtilityAccountAvailableBalance\":0,\"B2CWorkingAccountAvailableBalance\":0},\"OriginatorConversationID\":\"a399-4d4a-b8cf-cb102e9a964a31837\",\"ConversationID\":\"AG_20260728_010010030wi295nnsg88\",\"ResponseCode\":\"0\",\"ResponseDescription\":\"Success\"}","2026-07-28 15:12:04");
INSERT INTO `school_b2c_responses` VALUES ("79","","result","","","","","","","","","","2026-07-28 21:06:47");
INSERT INTO `school_b2c_responses` VALUES ("80","","result","","","","","","","","","","2026-07-28 21:10:14");
INSERT INTO `school_b2c_responses` VALUES ("81","","result","","","","","","","","","","2026-07-28 21:22:49");
INSERT INTO `school_b2c_responses` VALUES ("82","","result","","","","","","","","","","2026-07-28 21:22:59");
INSERT INTO `school_b2c_responses` VALUES ("83","","result","","","","","","","","","","2026-07-30 06:36:56");
INSERT INTO `school_b2c_responses` VALUES ("84","","timeout","timeout","B2C transaction timed out.","","","","","","","","2026-07-30 06:37:03");
INSERT INTO `school_b2c_responses` VALUES ("85","","timeout","timeout","B2C transaction timed out.","","","","","","","","2026-07-30 06:37:19");
INSERT INTO `school_b2c_responses` VALUES ("86","","result","","","","","","","","","","2026-07-30 06:37:20");
INSERT INTO `school_b2c_responses` VALUES ("87","","result","","","","","","","","","","2026-07-30 06:42:22");


-- Table structure for `school_balances`
DROP TABLE IF EXISTS `school_balances`;
CREATE TABLE `school_balances` (
  `school_id` int(11) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`school_id`),
  KEY `idx_balance` (`balance`),
  CONSTRAINT `fk_school_balances_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='School account balances';

INSERT INTO `school_balances` VALUES ("1","519.00","2026-07-08 00:25:30","2026-08-02 22:48:44");


-- Table structure for `school_breaks`
DROP TABLE IF EXISTS `school_breaks`;
CREATE TABLE `school_breaks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `break_name` varchar(100) NOT NULL,
  `break_type` enum('short_break','lunch_break','recess','other') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_break` (`school_id`,`break_name`,`start_time`),
  KEY `idx_school_id` (`school_id`),
  CONSTRAINT `school_breaks_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `school_breaks` VALUES ("1","1","Uji Break","short_break","10:00:00","10:30:00","1","2026-07-23 20:09:26","2026-07-23 20:09:26");
INSERT INTO `school_breaks` VALUES ("3","1","Lunch","lunch_break","13:00:00","13:59:00","1","2026-07-23 21:28:40","2026-07-23 21:28:40");


-- Table structure for `school_events`
DROP TABLE IF EXISTS `school_events`;
CREATE TABLE `school_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `event_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `event_type` enum('exam','meeting','sports','cultural','other') DEFAULT 'other',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_school_events_school` (`school_id`),
  KEY `idx_school_events_date` (`event_date`),
  CONSTRAINT `school_events_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for `school_sessions`
DROP TABLE IF EXISTS `school_sessions`;
CREATE TABLE `school_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_session_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `school_sessions` VALUES ("1","1","79a554b89d7faa5497e5366a948b63e1ebafddcac6592013dbf4bc38916883ca","2026-07-05 17:07:26","2026-07-04 18:07:26");
INSERT INTO `school_sessions` VALUES ("2","1","0b32a4c367bb6dde86aeb1b5539bc4f29534d1a09a0e32ea4a9b61b4873492d2","2026-07-05 20:30:53","2026-07-04 21:30:53");
INSERT INTO `school_sessions` VALUES ("3","1","e46f2d6963369e9ea14fb8ea86884b1443b3710f1021543fb9cdc284960645d2","2026-07-06 09:47:22","2026-07-05 10:47:23");
INSERT INTO `school_sessions` VALUES ("4","1","de32517c33b19bc744410230496fd4ab8808b331460d1eb4f222e893d25acb01","2026-07-06 09:59:45","2026-07-05 10:59:45");
INSERT INTO `school_sessions` VALUES ("5","1","e2efd128737d6df7090cf4b8b910ebc76c98b6bb07ea9490268adaa73582da0c","2026-07-06 18:49:35","2026-07-05 19:49:35");
INSERT INTO `school_sessions` VALUES ("6","1","3754666bdc07457555b6ed2b55582b679dce1715ba3daf21a0ef1d8c7c355f34","2026-07-23 04:19:29","2026-07-22 05:19:29");
INSERT INTO `school_sessions` VALUES ("7","1","275dd60dee6354ba8875610f0bec1aed39c2e9027fc6adf77f26cb5b1d359cf7","2026-07-23 14:47:33","2026-07-22 15:47:33");
INSERT INTO `school_sessions` VALUES ("8","1","d219e5defdc2aa5543a09f243042881538b139767f836efc58fca7e167c6223d","2026-07-23 14:57:52","2026-07-22 15:57:52");
INSERT INTO `school_sessions` VALUES ("9","1","953cb4254f0e21f94f574da8c3ce9af5374f3ab12e6d7941b8a0c0ce177fdc34","2026-07-23 17:37:49","2026-07-22 18:37:49");
INSERT INTO `school_sessions` VALUES ("10","1","482036542cd8bc09cbbcdc2f9ff0c64381ab5bf78677b587c1d7e17bdc52e53f","2026-07-24 16:26:06","2026-07-23 17:26:06");
INSERT INTO `school_sessions` VALUES ("11","2","afac73761082daf3cca490511453dfe544c019e448c8c237c45cb3863f729524","2026-07-25 00:47:36","2026-07-24 01:47:36");
INSERT INTO `school_sessions` VALUES ("12","2","7760f9238729bc3ab29f159038c424fdbc91ccb09db0c75af85bbdb0fb147781","2026-07-25 00:49:24","2026-07-24 01:49:24");
INSERT INTO `school_sessions` VALUES ("13","2","2ee31a70e280242c2b61d99d832dfc72b383f86b68299b65e3f1b2e07a9c25e4","2026-07-25 00:51:27","2026-07-24 01:51:27");
INSERT INTO `school_sessions` VALUES ("14","1","f9d21adb3614d8f976a58b03d06ee16ad6cf6188034f8da3dddf0c5c3fc5e32c","2026-07-25 00:53:13","2026-07-24 01:53:13");
INSERT INTO `school_sessions` VALUES ("15","2","2836dde3f778df1ff28fad662881c33b8f80040e75c64f62cb5e9a2eef2404ec","2026-07-25 20:38:04","2026-07-24 21:38:04");
INSERT INTO `school_sessions` VALUES ("16","1","905f63a583ea67080e3d0e2d102b7adbcb7a2ee65a1c639cec1a6542793ca142","2026-07-25 20:38:18","2026-07-24 21:38:18");
INSERT INTO `school_sessions` VALUES ("17","2","e01eb28b98e390900a83c4c60cb53ba8f3c1c4bd438b87abb0c47370abf648c0","2026-07-26 15:18:24","2026-07-25 16:18:24");
INSERT INTO `school_sessions` VALUES ("18","1","cd3af88d5d87506595e9bb3325054e692ce5f3127bad9d8bc01727c08f81e4db","2026-07-26 15:18:36","2026-07-25 16:18:36");
INSERT INTO `school_sessions` VALUES ("19","1","905a8ade1bffc3e209fadecc1a6ef65e2d53f27fdf0ff587ba5227afdd2b378f","2026-07-26 16:17:17","2026-07-25 17:17:17");
INSERT INTO `school_sessions` VALUES ("20","1","40b47616a9247b026a1e6aff750cbdd84720fbf7a86f7dfc686017e96b831015","2026-07-27 01:12:30","2026-07-26 02:12:30");
INSERT INTO `school_sessions` VALUES ("21","1","4ea64f7d6b1b071be943ef09287ff34c6e2cf33c72b1dc2dd6b8f3276c19df4b","2026-07-27 01:46:48","2026-07-26 02:46:48");
INSERT INTO `school_sessions` VALUES ("22","2","c9f003c7525e4ec3c908c9b38dd332a98727aafa247a98ad98382665c0239828","2026-07-27 12:59:16","2026-07-26 13:59:16");
INSERT INTO `school_sessions` VALUES ("23","1","298303a15c912276efd60056272f2946f5cadac432d8f5f86c22ce79a8bea953","2026-07-27 13:00:05","2026-07-26 14:00:05");
INSERT INTO `school_sessions` VALUES ("24","1","60dcd5cea807fad586c59f76fd03a60168013c98833206e9a49130cd8aff2337","2026-07-27 17:01:33","2026-07-26 18:01:33");
INSERT INTO `school_sessions` VALUES ("25","1","29536a9f3b0cc7ccc938fb8af8cef7e49f9ea08877e5038ca294b3835d7614ea","2026-07-28 12:03:02","2026-07-27 13:03:02");
INSERT INTO `school_sessions` VALUES ("26","2","76e4d0afd90573801dfe5be8bf6165c4beaa22a856381f01175e7ed62305f212","2026-07-28 16:45:06","2026-07-27 17:45:06");
INSERT INTO `school_sessions` VALUES ("27","1","b5fb582c89fcd04ea3bcb189adc1fbea30f8579d89e9972e3e10edd32dbdc86f","2026-07-28 16:45:24","2026-07-27 17:45:24");
INSERT INTO `school_sessions` VALUES ("28","2","f4fa85f6e5e8ae514b530707afaa9a727fb2816812d4e11042e9a5fd425914f1","2026-07-29 13:33:50","2026-07-28 14:33:50");
INSERT INTO `school_sessions` VALUES ("29","1","c3a446e3c8fca83b78dcc250c4ce148bacbcc0dc7951691f58bd22fc7d3af170","2026-07-29 13:34:12","2026-07-28 14:34:12");
INSERT INTO `school_sessions` VALUES ("30","2","928b804f2939768a2bb742a862ee47be940267eaf4f1d4dd8fd87aadd102d706","2026-07-29 23:54:48","2026-07-29 00:54:48");
INSERT INTO `school_sessions` VALUES ("31","1","d3fc961714b0bf5793f28edc983ab0f119a3b4c1212bb74aa37508d0c1bedcb8","2026-07-29 23:55:12","2026-07-29 00:55:12");
INSERT INTO `school_sessions` VALUES ("32","1","048dda7c4d947049caab444f048b091d4101dc49bc0dd47563781ecea632ecfa","2026-07-30 08:27:04","2026-07-29 09:27:04");
INSERT INTO `school_sessions` VALUES ("33","2","6302856f0ab83b3a70a402b697d4d7115ce00e6d3278ea160a5954867215fae7","2026-07-30 18:17:31","2026-07-29 19:17:31");
INSERT INTO `school_sessions` VALUES ("34","1","0e12242976cb7c7cb05c861a4c1b33329b356c672ad7315024b1cccd26f899e1","2026-07-30 18:17:44","2026-07-29 19:17:44");
INSERT INTO `school_sessions` VALUES ("35","2","e084ac6d83488865e0d2d62cb9a3132f6e684eb263f7826eb6c03a531683e63f","2026-07-31 11:03:36","2026-07-30 12:03:36");
INSERT INTO `school_sessions` VALUES ("36","2","4e7176c31125738de1d5dbf08953a873c8cdcf3750187c91e119e853d59dd122","2026-08-01 11:07:07","2026-07-31 12:07:07");
INSERT INTO `school_sessions` VALUES ("37","1","63ab38b9be70105870f7d1f6a4b2d384224cb460e75940781b3f459f93a43b55","2026-08-01 11:07:25","2026-07-31 12:07:25");
INSERT INTO `school_sessions` VALUES ("38","1","9af9dea4055f11400f309109ae94778f4c7ecc0326e5aa11c38f7e9581d765ad","2026-08-01 11:15:45","2026-07-31 12:15:45");
INSERT INTO `school_sessions` VALUES ("39","1","eda7bf8203eae206724a99b866d3db5dd00ae8848046d6164688b15a76de56de","2026-08-01 17:30:42","2026-07-31 18:30:42");
INSERT INTO `school_sessions` VALUES ("40","1","f6ee981b16b6adb6ffacaf30729a4d10ff421e4bf91af5e0c7963a3289c7e94f","2026-08-01 17:35:33","2026-07-31 18:35:33");
INSERT INTO `school_sessions` VALUES ("41","1","dadb0846d2c332e44b3524db0ad3cf24c7948b77667d39465cd40d388972be33","2026-08-01 20:50:26","2026-07-31 21:50:26");
INSERT INTO `school_sessions` VALUES ("42","1","0490170465ea8f6e0976d2dd9005f3e01ad5c52e4f8e8219a40205cde9b7774a","2026-08-02 12:26:08","2026-08-01 13:26:08");
INSERT INTO `school_sessions` VALUES ("43","2","cf0068b2b9610fc326542524e4b26f80e8c3a5dc68658b9f3723fe5185a948ad","2026-08-02 17:25:09","2026-08-01 18:25:09");
INSERT INTO `school_sessions` VALUES ("44","1","f53da9b3a3ad382275ba409f783c5d26ab142e1ca527f0939b03984971cb3c72","2026-08-02 17:25:22","2026-08-01 18:25:22");
INSERT INTO `school_sessions` VALUES ("45","1","448b92e45a9f464835b8dceb4bf12d3811484b6e5cfe5b0be51869e4fa572152","2026-08-02 17:28:13","2026-08-01 18:28:13");
INSERT INTO `school_sessions` VALUES ("46","1","4a2337b7f9267059d7f0ca92d0ae6d8f731cc5bd258bc75b8368f522bde0ddbf","2026-08-02 17:28:54","2026-08-01 18:28:54");
INSERT INTO `school_sessions` VALUES ("47","1","1cbf7c4aec102030fc6195f85b679ff3169167a3695128a1dda5d692035f63a5","2026-08-03 09:58:44","2026-08-02 10:58:44");
INSERT INTO `school_sessions` VALUES ("48","1","240b7bd036c0fd8ec06aea0c51ce01faad309aa4f981e55bd2543ed93a72235d","2026-08-03 14:26:32","2026-08-02 15:26:32");


-- Table structure for `school_withdrawals`
DROP TABLE IF EXISTS `school_withdrawals`;
CREATE TABLE `school_withdrawals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `balance_deducted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_number` (`reference_number`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_finance_manager_id` (`finance_manager_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=142 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `school_withdrawals` VALUES ("1","1","1","10.00","phone","Samwel okech","254745959757","for testing","testing","failed","WDR-20260709194002-1-231","2026-07-09 20:40:02","SQLSTATE[42S22]: Column not found: 1054 Unknown column \'originator_conversation_id\' in \'field list\'","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("2","1","1","10.00","phone","Samwel okech","254745959757","for testing","test","failed","WDR-20260709194218-1-699","2026-07-09 20:42:18","SQLSTATE[42S22]: Column not found: 1054 Unknown column \'originator_conversation_id\' in \'field list\'","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("3","1","1","10.00","phone","Samwel okech","254745959757","for testing","test","failed","WDR-20260709194318-1-831","2026-07-09 20:43:18","SQLSTATE[42S22]: Column not found: 1054 Unknown column \'conversation_id\' in \'field list\'","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("4","1","1","10.00","phone","Samwel okech","254745959757","for testing","test","failed","WDR-20260709194349-1-281","2026-07-09 20:43:49","SQLSTATE[42S22]: Column not found: 1054 Unknown column \'conversation_id\' in \'field list\'","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("5","1","1","10.00","phone","Samwel okech","254745959757","for testing","testing","failed","WDR-20260709194842-1-823","2026-07-09 20:48:42","SQLSTATE[42S22]: Column not found: 1054 Unknown column \'callback_payload\' in \'field list\'","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("6","1","1","10.00","phone","Samwel okech","254745959757","for testing","testing","failed","WDR-20260709194940-1-634","2026-07-09 20:49:40","Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .","c6fe-4c50-848a-aeda3881d02211","AG_20260709_0100103718mikj6ae3yu","","2040","{\"Result\":{\"ResultType\":0,\"ResultCode\":2040,\"ResultDesc\":\"Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .\",\"OriginatorConversationID\":\"c6fe-4c50-848a-aeda3881d02211\",\"ConversationID\":\"AG_20260709_0100103718mikj6ae3yu\",\"TransactionID\":\"UG90000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("7","1","1","10.00","phone","Samwel okech","254745959757","for testing","TEST","failed","WDR-20260712144114-1-633","2026-07-12 15:41:14","Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .","458f-4c59-86c9-6b9348359b4328597","AG_20260712_010010030xlsx3qv45vq","","2040","{\"Result\":{\"ResultType\":0,\"ResultCode\":2040,\"ResultDesc\":\"Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4328597\",\"ConversationID\":\"AG_20260712_010010030xlsx3qv45vq\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("8","1","1","10.00","phone","Samwel okech","254745959757","for testing","TEST","failed","WDR-20260712144132-1-846","2026-07-12 15:41:32","Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .","458f-4c59-86c9-6b9348359b4328612","AG_20260712_010010060xm61xrobdyn","","2040","{\"Result\":{\"ResultType\":0,\"ResultCode\":2040,\"ResultDesc\":\"Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4328612\",\"ConversationID\":\"AG_20260712_010010060xm61xrobdyn\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("9","1","1","10.00","phone","NDERE SENIOR SCHOOL","254745959757","for testing","TEST","failed","WDR-20260712144649-1-216","2026-07-12 15:46:49","Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .","458f-4c59-86c9-6b9348359b4328842","AG_20260712_010010370xszfoqvkj18","","2040","{\"Result\":{\"ResultType\":0,\"ResultCode\":2040,\"ResultDesc\":\"Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4328842\",\"ConversationID\":\"AG_20260712_010010370xszfoqvkj18\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("10","1","1","10.00","phone","NDERE SENIOR SCHOOL","254745959757","for testing","TEST","failed","WDR-20260712144841-1-581","2026-07-12 15:48:41","The ReceiverParty information is invalid.","4311-46f6-9a91-011b31669b70152307","AG_20260712_010010090xvdtwhb5m15","","7","{\"Result\":{\"ResultType\":0,\"ResultCode\":7,\"ResultDesc\":\"The ReceiverParty information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70152307\",\"ConversationID\":\"AG_20260712_010010090xvdtwhb5m15\",\"TransactionID\":\"UGC0907OD3\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("11","1","1","10.00","phone","NDERE SENIOR SCHOOL","254745959757","for testing","TEST","failed","WDR-20260712145222-1-942","2026-07-12 15:52:22","The ReceiverParty information is invalid.","4311-46f6-9a91-011b31669b70152526","AG_20260712_010010330y03yhoy6ul6","","7","{\"Result\":{\"ResultType\":0,\"ResultCode\":7,\"ResultDesc\":\"The ReceiverParty information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70152526\",\"ConversationID\":\"AG_20260712_010010330y03yhoy6ul6\",\"TransactionID\":\"UGC0X03E5X\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("12","1","1","10.00","phone","NDERE SENIOR SCHOOL","254745959757","for testing","TEST","failed","WDR-20260712145425-1-571","2026-07-12 15:54:25","The ReceiverParty information is invalid.","4311-46f6-9a91-011b31669b70152603","AG_20260712_010010090y2r36wms494","","7","{\"Result\":{\"ResultType\":0,\"ResultCode\":7,\"ResultDesc\":\"The ReceiverParty information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70152603\",\"ConversationID\":\"AG_20260712_010010090y2r36wms494\",\"TransactionID\":\"UGC0907OD4\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("13","1","1","10.00","phone","NDERE SENIOR SCHOOL","254745959757","for testing","TEST","failed","WDR-20260712145637-1-606","2026-07-12 15:56:37","The ReceiverParty information is invalid.","4311-46f6-9a91-011b31669b70152742","AG_20260712_010010150y5knhmysqvh","","7","{\"Result\":{\"ResultType\":0,\"ResultCode\":7,\"ResultDesc\":\"The ReceiverParty information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70152742\",\"ConversationID\":\"AG_20260712_010010150y5knhmysqvh\",\"TransactionID\":\"UGC0F06E05\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("14","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TESTING","failed","WDR-20260712150319-1-870","2026-07-12 16:03:19","The initiator information is invalid.","458f-4c59-86c9-6b9348359b4329651","AG_20260712_010010030ye6s6aobu6c","","2001","{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4329651\",\"ConversationID\":\"AG_20260712_010010030ye6s6aobu6c\",\"TransactionID\":\"UGC030DL5G\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("15","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","failed","WDR-20260712150457-1-576","2026-07-12 16:04:57","The initiator information is invalid.","4311-46f6-9a91-011b31669b70153288","AG_20260712_010010030yga79fmuqwz","","2001","{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70153288\",\"ConversationID\":\"AG_20260712_010010030yga79fmuqwz\",\"TransactionID\":\"UGC030DL5H\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("16","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","failed","WDR-20260712151824-1-470","2026-07-12 16:18:24","The initiator information is invalid.","4311-46f6-9a91-011b31669b70154141","AG_20260712_010010030yxlg6myxi2v","","2001","{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70154141\",\"ConversationID\":\"AG_20260712_010010030yxlg6myxi2v\",\"TransactionID\":\"UGC030DJK7\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("17","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","failed","WDR-20260712152033-1-782","2026-07-12 16:20:33","Failed to get M-Pesa access token.","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("18","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","failed","WDR-20260712152047-1-327","2026-07-12 16:20:47","Failed to get M-Pesa access token.","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("19","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TESTING","failed","WDR-20260712152328-1-780","2026-07-12 16:23:28","The initiator information is invalid.","4311-46f6-9a91-011b31669b70154453","AG_20260712_010010030z43tx1qrrp1","","2001","{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70154453\",\"ConversationID\":\"AG_20260712_010010030z43tx1qrrp1\",\"TransactionID\":\"UGC030DJK8\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("20","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","failed","WDR-20260712152501-1-191","2026-07-12 16:25:01","The initiator information is invalid.","458f-4c59-86c9-6b9348359b4330592","AG_20260712_010010030z63xyu21r6b","","2001","{\"Result\":{\"ResultType\":0,\"ResultCode\":2001,\"ResultDesc\":\"The initiator information is invalid.\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4330592\",\"ConversationID\":\"AG_20260712_010010030z63xyu21r6b\",\"TransactionID\":\"UGC030DJK9\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("21","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","failed","WDR-20260712152736-1-348","2026-07-12 16:27:36","The security credential is locked.","458f-4c59-86c9-6b9348359b4330735","AG_20260712_010010030z9eubmye52b","","8006","{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4330735\",\"ConversationID\":\"AG_20260712_010010030z9eubmye52b\",\"TransactionID\":\"UGC030DJKA\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("22","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","failed","WDR-20260712153235-1-572","2026-07-12 16:32:35","The security credential is locked.","4311-46f6-9a91-011b31669b70155080","AG_20260712_010010030zftpfo3ay3f","","8006","{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70155080\",\"ConversationID\":\"AG_20260712_010010030zftpfo3ay3f\",\"TransactionID\":\"UGC030DJKC\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("23","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","failed","WDR-20260712153342-1-370","2026-07-12 16:33:42","The security credential is locked.","4311-46f6-9a91-011b31669b70155176","AG_20260712_010010030zhagfgjtg5s","","8006","{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70155176\",\"ConversationID\":\"AG_20260712_010010030zhagfgjtg5s\",\"TransactionID\":\"UGC030DJKD\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("26","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","test","failed","WDR-20260712160414-1-143","2026-07-12 17:04:14","The security credential is locked.","4311-46f6-9a91-011b31669b70156977","AG_20260712_0100100310kmn2uz3j35","","8006","{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70156977\",\"ConversationID\":\"AG_20260712_0100100310kmn2uz3j35\",\"TransactionID\":\"UGC030DL5N\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("27","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","test","failed","WDR-20260712162714-1-828","2026-07-12 17:27:14","The security credential is locked.","4311-46f6-9a91-011b31669b70158461","AG_20260712_0100100311e3sib7qumz","","8006","{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70158461\",\"ConversationID\":\"AG_20260712_0100100311e3sib7qumz\",\"TransactionID\":\"UGC030DL5O\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("28","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","test","failed","WDR-20260712165256-1-930","2026-07-12 17:52:56","The security credential is locked.","458f-4c59-86c9-6b9348359b4335394","AG_20260712_0100100312b8x6r03cbf","","8006","{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4335394\",\"ConversationID\":\"AG_20260712_0100100312b8x6r03cbf\",\"TransactionID\":\"UGC030DL5Q\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("29","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","test","failed","WDR-20260712171347-1-529","2026-07-12 18:13:47","The security credential is locked.","4311-46f6-9a91-011b31669b70161381","AG_20260712_010010031321bsgc5y61","","8006","{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70161381\",\"ConversationID\":\"AG_20260712_010010031321bsgc5y61\",\"TransactionID\":\"UGC030DJKP\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("30","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","failed","WDR-20260712172958-1-746","2026-07-12 18:29:58","The security credential is locked.","4311-46f6-9a91-011b31669b70162365","AG_20260712_0100100313mtebcmhlh4","","8006","{\"Result\":{\"ResultType\":0,\"ResultCode\":8006,\"ResultDesc\":\"The security credential is locked.\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70162365\",\"ConversationID\":\"AG_20260712_0100100313mtebcmhlh4\",\"TransactionID\":\"UGC030DL5V\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("31","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","failed","WDR-20260712173338-1-784","2026-07-12 18:33:38","Insufficient balance","4311-46f6-9a91-011b31669b70162534","AG_20260712_0100100313rinqkn2x41","","4001","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70162534\",\"ConversationID\":\"AG_20260712_0100100313rinqkn2x41\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("32","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","failed","WDR-20260712173419-1-341","2026-07-12 18:34:19","Insufficient balance","4311-46f6-9a91-011b31669b70162580","AG_20260712_0100100313sdyhs8jx90","","4001","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70162580\",\"ConversationID\":\"AG_20260712_0100100313sdyhs8jx90\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("33","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","failed","WDR-20260712175542-1-908","2026-07-12 18:55:42","Insufficient balance","458f-4c59-86c9-6b9348359b4337854","AG_20260712_0100100314k229w74l1e","","4001","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"458f-4c59-86c9-6b9348359b4337854\",\"ConversationID\":\"AG_20260712_0100100314k229w74l1e\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("34","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","failed","WDR-20260712175629-1-652","2026-07-12 18:56:29","Insufficient balance","4311-46f6-9a91-011b31669b70163860","AG_20260712_0100100314kykha6f5zm","","4001","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70163860\",\"ConversationID\":\"AG_20260712_0100100314kykha6f5zm\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("35","1","","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","test","failed","WDR-20260712185342-1-715","2026-07-12 19:53:42","Manually marked as failed to free up available balance","4311-46f6-9a91-011b31669b70166344","AG_20260712_0100100316mj1zg0ulj8","","","","","");
INSERT INTO `school_withdrawals` VALUES ("36","1","","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","test","failed","WDR-20260712185659-1-244","2026-07-12 19:56:59","M-Pesa B2C connection error: Failed to connect to sandbox.safaricom.co.ke port 443 after 21038 ms: Couldn\'t connect to server","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("37","1","","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","test","failed","WDR-20260712185800-1-237","2026-07-12 19:58:00","Insufficient balance","4311-46f6-9a91-011b31669b70166588","AG_20260712_0100100316s3pshk8cxg","","4001","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70166588\",\"ConversationID\":\"AG_20260712_0100100316s3pshk8cxg\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("38","1","","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","test","failed","WDR-20260712190335-1-249","2026-07-12 20:03:35","Insufficient balance","4311-46f6-9a91-011b31669b70166855","AG_20260712_0100100316ziy0r7g334","","4001","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"4311-46f6-9a91-011b31669b70166855\",\"ConversationID\":\"AG_20260712_0100100316ziy0r7g334\",\"TransactionID\":\"UGC0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("39","1","1","10.00","till","NDERE SENIOR SCHOOL","4071899","for testing","test","failed","WDR-20260712191612-1-241","2026-07-12 20:16:12","Manually marked as failed to free up available balance","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("40","1","","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","test","failed","WDR-20260714235027-1-640","2026-07-15 00:50:27","Insufficient balance","ff3e-4fa4-abc0-8eb3aa92c0d9110087","AG_20260715_0100100301s7pngznnhy","","4001","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"ff3e-4fa4-abc0-8eb3aa92c0d9110087\",\"ConversationID\":\"AG_20260715_0100100301s7pngznnhy\",\"TransactionID\":\"UGF0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("41","1","","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","failed","WDR-20260715001051-1-510","2026-07-15 01:10:51","Insufficient balance","3a62-4214-aa55-1c05a6d85a1c29486","AG_20260715_0100100302ighd81jdj9","","4001","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"3a62-4214-aa55-1c05a6d85a1c29486\",\"ConversationID\":\"AG_20260715_0100100302ighd81jdj9\",\"TransactionID\":\"UGF0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("42","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","test","failed","WDR-20260719192524-1-645","2026-07-19 20:25:24","Manually marked as failed to free up available balance","7cd0-4a28-8d04-cb27fe4732c656325","AG_20260719_0100100317r35su95l4f","","","","","");
INSERT INTO `school_withdrawals` VALUES ("62","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","failed","WDR-20260720004156-1-859","2026-07-20 01:41:56","Insufficient balance","7cd0-4a28-8d04-cb27fe4732c675739","AG_20260720_0100100303magp3v5e6s","","4001","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"7cd0-4a28-8d04-cb27fe4732c675739\",\"ConversationID\":\"AG_20260720_0100100303magp3v5e6s\",\"TransactionID\":\"UGK0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("63","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","test","testing","failed","WDR-20260720040712-1-863","2026-07-20 05:07:12","Insufficient balance","6839-428d-8589-2bcb00296f3788158","AG_20260720_010010030ay9icuyg8xg","","4001","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"6839-428d-8589-2bcb00296f3788158\",\"ConversationID\":\"AG_20260720_010010030ay9icuyg8xg\",\"TransactionID\":\"UGK0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("64","1","","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","test","failed","WDR-20260720041019-1-108","2026-07-20 05:10:19","Insufficient balance","6839-428d-8589-2bcb00296f3788315","AG_20260720_010010030b29th2u5c1g","","4001","{\"Result\":{\"ResultType\":0,\"ResultCode\":4001,\"ResultDesc\":\"Insufficient balance\",\"OriginatorConversationID\":\"6839-428d-8589-2bcb00296f3788315\",\"ConversationID\":\"AG_20260720_010010030b29th2u5c1g\",\"TransactionID\":\"UGK0000000\",\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","","");
INSERT INTO `school_withdrawals` VALUES ("65","1","","50.00","library_fine_payment","Library Fine Payment","TEST-1784734565","","Fine payment for book ID: 1, Fine ID: 5","completed","TEST-1784734565","2026-07-22 18:36:05","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("66","1","","1.00","library_fine_payment","Library Fine Payment","UGMN80E49M","","Fine payment for book ID: 4, Fine ID: 6","completed","UGMN80E49M","2026-07-22 19:40:16","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("67","1","","1.00","library_fine_payment","Library Fine Payment","UGMN80DZXL","","Fine payment for book ID: 4, Fine ID: 6","completed","UGMN80DZXL","2026-07-22 19:42:04","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("68","1","","1.00","library_fine_payment","Library Fine Payment","UGMN80E4ND","","Fine payment for book ID: 4, Fine ID: 6","completed","UGMN80E4ND","2026-07-22 19:51:25","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("69","1","","1.00","library_fine_payment","Library Fine Payment","UGMN80ELXU","","Fine payment for book ID: 4, Fine ID: 6","completed","UGMN80ELXU","2026-07-22 22:14:35","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("70","1","","1.00","library_fine_payment","Library Fine Payment","UGMN80ES0M","","Fine payment for book ID: 4, Fine ID: 6","completed","UGMN80ES0M","2026-07-22 22:30:24","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("71","1","","1.00","library_fine_payment","Library Fine Payment","UGMN80ES2Y","","Fine payment for book ID: 4, Fine ID: 6","completed","UGMN80ES2Y","2026-07-22 22:35:07","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("73","1","","1.00","library_fine_payment","Library Fine Payment","UGON80IRKO","","Fine payment for book ID: 4, Fine ID: 6","completed","UGON80IRKO","2026-07-24 01:40:18","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("74","1","","1.00","library_fine_payment","Library Fine Payment","UGON80L1VF","","Fine payment for book ID: 4, Fine ID: 26","completed","UGON80L1VF","2026-07-24 16:28:04","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("75","1","","1.00","library_fine_payment","Library Fine Payment","UGPN80MX5K","","Fine payment for book ID: 4, Fine ID: 28","completed","UGPN80MX5K","2026-07-25 02:50:40","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("76","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","","WDR-20260725020213-1-587","2026-07-25 03:02:13","The service request is processed successfully.","6e47-4967-bcb9-74445bede7d2149876","AG_20260725_0100100306hfuhjt5vaj","UGP030DS9I","0","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2149876\",\"ConversationID\":\"AG_20260725_0100100306hfuhjt5vaj\",\"TransactionID\":\"UGP030DS9I\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DS9I\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:01:31\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020629.44},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 03:02:17","2026-07-25 03:02:17");
INSERT INTO `school_withdrawals` VALUES ("77","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","","WDR-20260725020316-1-645","2026-07-25 03:03:16","The service request is processed successfully.","6e47-4967-bcb9-74445bede7d2149944","AG_20260725_0100100306isnutdzh5z","UGP030DTRC","0","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2149944\",\"ConversationID\":\"AG_20260725_0100100306isnutdzh5z\",\"TransactionID\":\"UGP030DTRC\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRC\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:02:35\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020585.84},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 03:03:20","2026-07-25 03:03:20");
INSERT INTO `school_withdrawals` VALUES ("78","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","","WDR-20260725020521-1-535","2026-07-25 03:05:21","The service request is processed successfully.","b13d-4e1d-8fb5-0f0d66c4323015398","AG_20260725_0100100306lgkp3uxieq","UGP030DS9J","0","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"b13d-4e1d-8fb5-0f0d66c4323015398\",\"ConversationID\":\"AG_20260725_0100100306lgkp3uxieq\",\"TransactionID\":\"UGP030DS9J\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DS9J\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:04:39\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020542.24},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 03:05:24","2026-07-25 03:05:24");
INSERT INTO `school_withdrawals` VALUES ("79","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","","WDR-20260725020823-1-789","2026-07-25 03:08:23","The service request is processed successfully.","6e47-4967-bcb9-74445bede7d2150262","AG_20260725_0100100306pdhg6hp4zb","UGP030DTRD","0","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2150262\",\"ConversationID\":\"AG_20260725_0100100306pdhg6hp4zb\",\"TransactionID\":\"UGP030DTRD\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRD\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:07:41\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020498.64},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 03:08:27","2026-07-25 03:08:27");
INSERT INTO `school_withdrawals` VALUES ("80","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","completed","WDR-20260725021055-1-871","2026-07-25 03:10:55","The service request is processed successfully.","b13d-4e1d-8fb5-0f0d66c4323015602","AG_20260725_0100100306smeff2m19e","UGP030DS9L","0","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"b13d-4e1d-8fb5-0f0d66c4323015602\",\"ConversationID\":\"AG_20260725_0100100306smeff2m19e\",\"TransactionID\":\"UGP030DS9L\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DS9L\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:10:13\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020455.04},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 03:19:27","2026-07-25 03:19:27");
INSERT INTO `school_withdrawals` VALUES ("81","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TESTING","","WDR-20260725021322-1-500","2026-07-25 03:13:22","The service request is processed successfully.","b13d-4e1d-8fb5-0f0d66c4323015718","AG_20260725_0100100306vs08jfd9dp","UGP030DTRE","0","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"b13d-4e1d-8fb5-0f0d66c4323015718\",\"ConversationID\":\"AG_20260725_0100100306vs08jfd9dp\",\"TransactionID\":\"UGP030DTRE\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRE\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:12:40\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020411.44},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 03:13:26","2026-07-25 03:13:26");
INSERT INTO `school_withdrawals` VALUES ("82","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","completed","WDR-20260725021845-1-829","2026-07-25 03:18:45","The service request is processed successfully.","6e47-4967-bcb9-74445bede7d2150843","AG_20260725_01001003072oz2xm4nf9","UGP030DS9N","0","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2150843\",\"ConversationID\":\"AG_20260725_01001003072oz2xm4nf9\",\"TransactionID\":\"UGP030DS9N\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DS9N\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:18:03\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020367.84},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 03:18:48","2026-07-25 03:18:48");
INSERT INTO `school_withdrawals` VALUES ("83","1","","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","completed","WDR-20260725022249-1-473","2026-07-25 03:22:49","The service request is processed successfully.","b13d-4e1d-8fb5-0f0d66c4323016055","AG_20260725_01001003077xj8a055yi","UGP030DTRI","0","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"b13d-4e1d-8fb5-0f0d66c4323016055\",\"ConversationID\":\"AG_20260725_01001003077xj8a055yi\",\"TransactionID\":\"UGP030DTRI\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRI\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:22:07\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020324.24},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 03:22:53","2026-07-25 03:22:53");
INSERT INTO `school_withdrawals` VALUES ("84","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","completed","WDR-20260725022632-1-945","2026-07-25 03:26:32","The service request is processed successfully.","6e47-4967-bcb9-74445bede7d2151316","AG_20260725_0100100307cpjvnuq7c3","UGP030DTRJ","0","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"6e47-4967-bcb9-74445bede7d2151316\",\"ConversationID\":\"AG_20260725_0100100307cpjvnuq7c3\",\"TransactionID\":\"UGP030DTRJ\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DTRJ\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:25:50\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020280.64},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 03:26:36","2026-07-25 03:26:36");
INSERT INTO `school_withdrawals` VALUES ("85","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","TEST","completed","WDR-20260725030033-1-827","2026-07-25 04:00:33","The service request is processed successfully.","b13d-4e1d-8fb5-0f0d66c4323017549","AG_20260725_0100100308kgk5tgbi2e","UGP030DS9S","0","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"b13d-4e1d-8fb5-0f0d66c4323017549\",\"ConversationID\":\"AG_20260725_0100100308kgk5tgbi2e\",\"TransactionID\":\"UGP030DS9S\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":10},{\"Key\":\"TransactionReceipt\",\"Value\":\"UGP030DS9S\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - John Doe\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"25.07.2026 03:59:51\"},{\"Key\":\"B2CUtilityAccountAvailableFunds\",\"Value\":3020237.04},{\"Key\":\"B2CWorkingAccountAvailableFunds\",\"Value\":584459.00},{\"Key\":\"B2CRecipientIsRegisteredCustomer\",\"Value\":\"Y\"},{\"Key\":\"B2CChargesPaidAccountAvailableFunds\",\"Value\":-2420.00}]},\"ReferenceData\":{\"ReferenceItem\":{\"Key\":\"QueueTimeoutURL\",\"Value\":\"https:\\/\\/internalsandbox.safaricom.co.ke\\/mpesa\\/b2cresults\\/v1\\/submit\"}}}}","2026-07-25 04:00:37","2026-07-25 04:00:37");
INSERT INTO `school_withdrawals` VALUES ("86","1","","1.00","cash","NDERE SENIOR SCHOOL","0745959757","","TESTING","completed","WDR-20260727191217-1","2026-07-27 20:12:17","Manual cash withdrawal processed by admin","","","","","","2026-07-27 20:12:17","2026-07-27 20:12:17");
INSERT INTO `school_withdrawals` VALUES ("87","1","","1.00","cash","NDERE SENIOR SCHOOL","0745959757","","TESTING","completed","WDR-20260727191551-1","2026-07-27 20:15:51","Manual cash withdrawal processed by admin","","","","","","2026-07-27 20:15:51","2026-07-27 20:15:51");
INSERT INTO `school_withdrawals` VALUES ("88","1","","1.00","cash","NDERE SENIOR SCHOOL","0745959757","","TESTING","completed","WDR-20260727191847-1","2026-07-27 20:18:47","Manual cash withdrawal processed by admin","","","","","","2026-07-27 20:18:47","2026-07-27 20:18:47");
INSERT INTO `school_withdrawals` VALUES ("89","1","","1.00","cash","NDERE SENIOR SCHOOL","0745959757","","TEST","completed","WDR-20260727200339-1","2026-07-27 21:03:39","Manual cash withdrawal processed by admin","","","","","","2026-07-27 21:03:39","2026-07-27 21:03:39");
INSERT INTO `school_withdrawals` VALUES ("90","1","","1.00","cash","NDERE SENIOR SCHOOL","0745959757","","TEST","completed","WDR-20260727200535-1","2026-07-27 21:05:35","Manual cash withdrawal processed by admin","","","","","","2026-07-27 21:05:35","2026-07-27 21:05:35");
INSERT INTO `school_withdrawals` VALUES ("102","1","","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","yufsdyujklbjk","completed","WDR-20260727215416-1-279","2026-07-27 22:54:16","The service request is processed successfully.","8544-4e2a-bd68-bbd981b518db52627","AG_20260727_010010031d2dx77ekv48","TEST1785182157","0","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"The service request is processed successfully.\",\"OriginatorConversationID\":\"8544-4e2a-bd68-bbd981b518db52627\",\"ConversationID\":\"AG_20260727_010010031d2dx77ekv48\",\"TransactionID\":\"TEST1785182157\",\"ResultParameters\":{\"ResultParameter\":[{\"Key\":\"TransactionAmount\",\"Value\":\"10.00\"},{\"Key\":\"TransactionReceipt\",\"Value\":\"TEST1785182157\"},{\"Key\":\"ReceiverPartyPublicName\",\"Value\":\"254708374149 - Test\"},{\"Key\":\"TransactionCompletedDateTime\",\"Value\":\"27.07.2026 21:55:57\"}]}}}","2026-07-27 22:55:57","2026-07-27 22:55:57");
INSERT INTO `school_withdrawals` VALUES ("112","1","","1.00","library_fine_payment","Library Fine Payment","UGSN80YOJL","","Fine payment for book ID: 4, Fine ID: 28","completed","UGSN80YOJL","2026-07-28 00:06:05","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("113","1","","1.00","library_fine_payment","Library Fine Payment","UGSN80YNXT","","Fine payment for book ID: 4, Fine ID: 28","completed","UGSN80YNXT","2026-07-28 00:08:47","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("115","1","","1.00","library_fine_payment","Library Fine Payment","UGSN80YL5F","","Fine payment for book ID: 4, Fine ID: 28","completed","UGSN80YL5F","2026-07-28 00:12:58","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("119","1","","1.00","library_fine_payment","Library Fine Payment","UGSN80YMV9","","Fine payment for book ID: 4, Fine ID: 28","completed","UGSN80YMV9","2026-07-28 03:57:38","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("123","1","","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","YUFGJKBNJKL","completed","WDR-20260728140107-1-657","2026-07-28 15:01:07","Success","b8a0-4b73-8c6c-70a18412423218383","AG_20260728_010010030w5xf04rpc5q","TST12345678","0","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"Success\",\"OriginatorConversationID\":\"b8a0-4b73-8c6c-70a18412423218383\",\"ConversationID\":\"AG_20260728_010010030w5xf04rpc5q\",\"TransactionID\":\"TST12345678\",\"ReceiptNumber\":\"TEST123\",\"TransactionDate\":\"20260728150100\",\"Amount\":10,\"B2CRecipientIsRegisteredCustomer\":\"Y\",\"B2CChargesPaidAccount\":\"MSISDN\",\"TransactionReason\":\"SalaryPayment\",\"DebitAccountChargedAmount\":10.5,\"DebitAccountCharged\":600997,\"DebitAccountReference\":\"test-ref\",\"KESAccountBalance\":1000,\"UtilityAccountBalance\":0,\"B2CRecipientAccount\":\"254708374149\",\"B2CUtilityAccountAvailableBalance\":0,\"B2CWorkingAccountAvailableBalance\":0},\"OriginatorConversationID\":\"b8a0-4b73-8c6c-70a18412423218383\",\"ConversationID\":\"AG_20260728_010010030w5xf04rpc5q\",\"ResponseCode\":\"0\",\"ResponseDescription\":\"Success\"}","2026-07-28 15:02:48","2026-07-28 15:02:48");
INSERT INTO `school_withdrawals` VALUES ("124","1","1","10.00","phone","NDERE SENIOR SCHOOL","254708374149","for testing","CFYUVJKBJJKLN","completed","WDR-20260728141033-1-659","2026-07-28 15:10:33","Success","a399-4d4a-b8cf-cb102e9a964a31837","AG_20260728_010010030wi295nnsg88","TST12345678","0","{\"Result\":{\"ResultType\":0,\"ResultCode\":0,\"ResultDesc\":\"Success\",\"OriginatorConversationID\":\"a399-4d4a-b8cf-cb102e9a964a31837\",\"ConversationID\":\"AG_20260728_010010030wi295nnsg88\",\"TransactionID\":\"TST12345678\",\"ReceiptNumber\":\"TEST123\",\"TransactionDate\":\"20260728151000\",\"Amount\":10,\"B2CRecipientIsRegisteredCustomer\":\"Y\",\"B2CChargesPaidAccount\":\"MSISDN\",\"TransactionReason\":\"SalaryPayment\",\"DebitAccountChargedAmount\":10.5,\"DebitAccountCharged\":600997,\"DebitAccountReference\":\"test-ref\",\"KESAccountBalance\":1000,\"UtilityAccountBalance\":0,\"B2CRecipientAccount\":\"254708374149\",\"B2CUtilityAccountAvailableBalance\":0,\"B2CWorkingAccountAvailableBalance\":0},\"OriginatorConversationID\":\"a399-4d4a-b8cf-cb102e9a964a31837\",\"ConversationID\":\"AG_20260728_010010030wi295nnsg88\",\"ResponseCode\":\"0\",\"ResponseDescription\":\"Success\"}","2026-07-28 15:11:12","2026-07-28 15:11:12");
INSERT INTO `school_withdrawals` VALUES ("135","1","","1.00","library_fine_payment","Library Fine Payment","UH2N81KMHH","","Fine payment for book ID: 4, Fine ID: 28","completed","UH2N81KMHH","2026-08-02 12:09:14","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("136","1","","1.00","library_fine_payment","Library Fine Payment","UH2N81M9DY","","Fine payment for book ID: 4, Fine ID: 28","completed","UH2N81M9DY","2026-08-02 18:10:58","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("137","1","","1.00","library_fine_payment","Library Fine Payment","UH2N81MB50","","Fine payment for book ID: 4, Fine ID: 28","completed","UH2N81MB50","2026-08-02 18:18:43","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("138","1","","1.00","library_fine_payment","Library Fine Payment","UH2N81MCZ1","","Fine payment for book ID: 4, Fine ID: 28","completed","UH2N81MCZ1","2026-08-02 18:33:22","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("139","1","","50.00","library_fine_payment","Library Fine Payment","TEST123456","","Fine payment for book ID: 4, Fine ID: 28","completed","TEST123456","2026-08-02 18:44:18","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("140","1","","1.00","library_fine_payment","Library Fine Payment","UH2N81MACX","","Fine payment for book ID: 4, Fine ID: 28","completed","UH2N81MACX","2026-08-02 18:45:07","","","","","","","","");
INSERT INTO `school_withdrawals` VALUES ("141","1","","1.00","library_fine_payment","Library Fine Payment","UH2N81MEDX","","Fine payment for book ID: 4, Fine ID: 28","completed","UH2N81MEDX","2026-08-02 18:48:41","","","","","","","","");


-- Table structure for `schools`
DROP TABLE IF EXISTS `schools`;
CREATE TABLE `schools` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_code` varchar(20) NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `withdrawal_pin` varchar(255) DEFAULT NULL,
  `verification_code` varchar(10) DEFAULT NULL,
  `verification_expiry` datetime DEFAULT NULL,
  `sms_verification_code` varchar(10) DEFAULT NULL,
  `sms_verification_expiry` datetime DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `county` varchar(100) NOT NULL,
  `school_type` enum('Primary','Secondary','College','University') NOT NULL,
  `admission_prefix` varchar(50) DEFAULT NULL,
  `status` enum('pending','active','suspended') DEFAULT 'pending',
  `logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `min_subjects` int(11) DEFAULT 7,
  `max_subjects` int(11) DEFAULT 8,
  `sms_provider` enum('mobitech','textsms') DEFAULT 'mobitech',
  `mobitech_api_key` varchar(255) DEFAULT NULL,
  `mobitech_sender_id` varchar(50) DEFAULT NULL,
  `textsms_api_key` varchar(255) DEFAULT NULL,
  `textsms_partner_id` varchar(50) DEFAULT NULL,
  `textsms_sender_id` varchar(50) DEFAULT NULL,
  `sms_sender_id` varchar(50) DEFAULT 'KenyaEduHub',
  `sms_enabled` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `school_code` (`school_code`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `schools` VALUES ("1","SCH176C0AD1","NDERE SENIOR SCHOOL","otisbrian46@gmail.com","$2y$10$cVlyR0SCbJTqz7twg0/a2.VVdmROdKmDt.8A5kgi.f8Bm1yEK.6oG","$2y$10$VQqktljfK2Zf6wOuUT/U3.pRJ7tl1M7Gk0NAMqqDtHv7A8YNpcDci","","","714985","2026-07-31 18:02:55","0745959757","Kisumu\n40100 kisumu","HOMABAY","Secondary","NDS","active","../uploads/schools/school_1_1783185722.png","2026-07-04 18:06:57","2026-07-31 18:47:55","7","8","textsms","fbec514c17f9f084b9d8745664b634e34e16b5d089b47224ec3b47b9303f1b54","FULL_CIRCLE","59c5825a8c0d1491e6d47cc0889c1ee0","13994","TextSMS","","1");
INSERT INTO `schools` VALUES ("2","SCH1432B168","WIOBIERO SENIOR SCHOOL","otienobrian029@gmail.com","$2y$10$QyUnDPtCEiqQpcX7uPOCruiTGtM64pNztG20LBJS7/v9snfRz.eTu","","","","","","0745959757","Kisumu\n40100 kisumu","SIAYA","Primary","NDS","active","","2026-07-24 01:45:36","2026-07-24 01:48:32","7","8","mobitech","","","","","","KenyaEduHub","0");


-- Table structure for `sms_balance`
DROP TABLE IF EXISTS `sms_balance`;
CREATE TABLE `sms_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `provider` enum('mobitech','textsms') NOT NULL,
  `balance` int(11) DEFAULT 0,
  `last_checked` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_school_provider` (`school_id`,`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for `sms_logs`
DROP TABLE IF EXISTS `sms_logs`;
CREATE TABLE `sms_logs` (
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_recipient_phone` (`recipient_phone`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sms_logs` VALUES ("1","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Results for BRIAN ONYANGO OTIENO (NDS/1)\nTotal: 452, Avg: 64.6, Grade: B+","results","textsms","","failed","{\"response-code\":1003,\"response-description\":\"Validation Errors. Check errors and try again\",\"errors\":{\"shortcode\":{\"Shortcode\":\"Shortcode must not be empty\"}}}","","","2026-07-26 01:50:15","2026-07-26 01:50:15","");
INSERT INTO `sms_logs` VALUES ("2","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Results for BRIAN ONYANGO OTIENO (NDS/1)\nTotal: 125, Avg: 62.5, Grade: D+","results","textsms","","failed","{\"response-code\":1003,\"response-description\":\"Validation Errors. Check errors and try again\",\"errors\":{\"shortcode\":{\"Shortcode\":\"Shortcode must not be empty\"}}}","","","2026-07-26 01:50:16","2026-07-26 01:50:16","");
INSERT INTO `sms_logs` VALUES ("3","1","0745959757","","parent","Test SMS from Kenya EduHub","general","textsms","","failed","{\"response-code\":1003,\"response-description\":\"Validation Errors. Check errors and try again\",\"errors\":{\"shortcode\":{\"Shortcode\":\"Shortcode must not be empty\"}}}","","","2026-07-26 01:52:26","2026-07-26 01:52:26","");
INSERT INTO `sms_logs` VALUES ("4","1","0745959757","","parent","Test SMS from Kenya EduHub","general","textsms","","failed","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768124686,\"networkid\":1}]}","","","2026-07-26 01:54:31","2026-07-26 01:54:31","");
INSERT INTO `sms_logs` VALUES ("5","1","0745959757","","parent","Test SMS from Kenya EduHub","general","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768124732,\"networkid\":1}]}","768124732","","2026-07-26 01:55:11","2026-07-26 01:55:11","");
INSERT INTO `sms_logs` VALUES ("6","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Results for BRIAN ONYANGO OTIENO (NDS/1)\nTotal: 452, Avg: 64.6, Grade: B+","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768124769,\"networkid\":1}]}","768124769","","2026-07-26 01:55:42","2026-07-26 01:55:42","");
INSERT INTO `sms_logs` VALUES ("7","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Results for BRIAN ONYANGO OTIENO (NDS/1)\nTotal: 125, Avg: 62.5, Grade: D+","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768124772,\"networkid\":1}]}","768124772","","2026-07-26 01:55:42","2026-07-26 01:55:42","");
INSERT INTO `sms_logs` VALUES ("8","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nAGR: - (-)\nBIO: 77.00 (A-)\nBUSI: - (-)\nCHEM: 70.00 (A)\nCRE: - (-)\nENG: 55.00 (C+)\nGEOG: 60.00 (B+)\nHIST: 80.00 (A)\nKISW: 60.00 (B-)\nMATH: 50.00 (B-)\nPHY: 50.00 (B+)\n\nTotal: 452, Avg: 64.6\nFinal Grade: B+","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768124909,\"networkid\":1}]}","768124909","","2026-07-26 01:57:23","2026-07-26 01:57:23","");
INSERT INTO `sms_logs` VALUES ("9","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nCHEM: 65.00 (A)\nPHY: 60.00 (A)\n\nTotal: 125, Avg: 62.5\nFinal Grade: D+","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768124910,\"networkid\":1}]}","768124910","","2026-07-26 01:57:23","2026-07-26 01:57:23","");
INSERT INTO `sms_logs` VALUES ("10","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nAGR: - (-)\nBIO: 77.00 (A-)\nBUSI: - (-)\nCHEM: 70.00 (A)\nCRE: - (-)\nENG: 55.00 (C+)\nGEOG: 60.00 (B+)\nHIST: 80.00 (A)\nKISW: 60.00 (B-)\nMATH: 50.00 (B-)\nPHY: 50.00 (B+)\n\nTotal: 452, Avg: 64.6\nFinal Grade: B+","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768124975,\"networkid\":1}]}","768124975","","2026-07-26 01:58:15","2026-07-26 01:58:15","");
INSERT INTO `sms_logs` VALUES ("11","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\n\nTotal: 452, Avg: 64.6\nFinal Grade: B+","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768125267,\"networkid\":1}]}","768125267","","2026-07-26 02:00:50","2026-07-26 02:00:50","");
INSERT INTO `sms_logs` VALUES ("12","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\n\nTotal: 452, Avg: 64.6\nFinal Grade: B+","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768125315,\"networkid\":1}]}","768125315","","2026-07-26 02:01:19","2026-07-26 02:01:19","");
INSERT INTO `sms_logs` VALUES ("13","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\n\nTotal: 452, Avg: 64.6\nFinal Grade: B+","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768125481,\"networkid\":1}]}","768125481","","2026-07-26 02:03:23","2026-07-26 02:03:23","");
INSERT INTO `sms_logs` VALUES ("14","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A-)\nCHEMISTRY: 70.00 (A)\nENGLISH: 55.00 (C+)\nGEOGRAPHY: 60.00 (B+)\nHISTORY AND GOVERNMENT: 80.00 (A)\nKISWAHILI: 60.00 (B-)\nMATHEMATICS: 50.00 (B-)\nPHYSICS: 50.00 (B+)\n\nTotal: 452, Avg: 64.6\nFinal Grade: B+","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768125642,\"networkid\":1}]}","768125642","","2026-07-26 02:05:10","2026-07-26 02:05:10","");
INSERT INTO `sms_logs` VALUES ("15","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A-)\nCHEMISTRY: 70.00 (A)\nENGLISH: 55.00 (C+)\nGEOGRAPHY: 60.00 (B+)\nHISTORY AND GOVERNMENT: 80.00 (A)\nKISWAHILI: 60.00 (B-)\nMATHEMATICS: 50.00 (B-)\nPHYSICS: 50.00 (B+)\n\nTotal: 452, Points: 68\nFinal Grade: B+","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768125850,\"networkid\":1}]}","768125850","","2026-07-26 02:07:35","2026-07-26 02:07:35","");
INSERT INTO `sms_logs` VALUES ("16","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A-)\nCHEMISTRY: 70.00 (A)\nENGLISH: 55.00 (C+)\nGEOGRAPHY: 60.00 (B+)\nHISTORY AND GOVERNMENT: 80.00 (A)\nKISWAHILI: 60.00 (B-)\nMATHEMATICS: 50.00 (B-)\nPHYSICS: 50.00 (B+)\n\nTotal: 452, Points: 68\nFinal Grade: B+","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768126054,\"networkid\":1}]}","768126054","","2026-07-26 02:10:03","2026-07-26 02:10:03","");
INSERT INTO `sms_logs` VALUES ("17","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A-)\nCHEMISTRY: 70.00 (A)\nENGLISH: 55.00 (C+)\nGEOGRAPHY: 60.00 (B+)\nHISTORY AND GOVERNMENT: 80.00 (A)\nKISWAHILI: 60.00 (B-)\nMATHEMATICS: 50.00 (B-)\nPHYSICS: 50.00 (B+)\n\nTotal: 452, Points: 68\nFinal Grade: B+","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768126416,\"networkid\":1}]}","768126416","","2026-07-26 02:14:16","2026-07-26 02:14:16","");
INSERT INTO `sms_logs` VALUES ("18","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A-)\nCHEMISTRY: 70.00 (A)\nENGLISH: 55.00 (C+)\nGEOGRAPHY: 60.00 (B+)\nHISTORY AND GOVERNMENT: 80.00 (A)\nKISWAHILI: 60.00 (B-)\nMATHEMATICS: 50.00 (B-)\nPHYSICS: 50.00 (B+)\n\nTotal: 452, Points: 68\nFinal Grade: B+","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768126584,\"networkid\":1}]}","768126584","","2026-07-26 02:16:06","2026-07-26 02:16:06","");
INSERT INTO `sms_logs` VALUES ("19","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A-)\nCHEMISTRY: 70.00 (A)\nENGLISH: 55.00 (C+)\nGEOGRAPHY: 60.00 (B+)\nHISTORY AND GOVERNMENT: 80.00 (A)\nKISWAHILI: 60.00 (B-)\nMATHEMATICS: 50.00 (B-)\nPHYSICS: 50.00 (B+)\n\nTotal: 452, Points: 68\nFinal Grade: B+","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768127230,\"networkid\":1}]}","768127230","","2026-07-26 02:23:14","2026-07-26 02:23:14","");
INSERT INTO `sms_logs` VALUES ("20","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A-)\nCHEMISTRY: 70.00 (A)\nENGLISH: 55.00 (CPlus)\nGEOGRAPHY: 60.00 (BPlus)\nHISTORY AND GOVERNMENT: 80.00 (A)\nKISWAHILI: 60.00 (B-)\nMATHEMATICS: 50.00 (B-)\nPHYSICS: 50.00 (BPlus)\n\nTotal: 452, Points: 68\nFinal Grade: BPlus","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768127562,\"networkid\":1}]}","768127562","","2026-07-26 02:26:40","2026-07-26 02:26:40","");
INSERT INTO `sms_logs` VALUES ("21","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A-)\nCHEMISTRY: 70.00 (A)\nENGLISH: 55.00 (C+)\nGEOGRAPHY: 60.00 (B+)\nHISTORY AND GOVERNMENT: 80.00 (A)\nKISWAHILI: 60.00 (B-)\nMATHEMATICS: 50.00 (B-)\nPHYSICS: 50.00 (B+)\n\nTotal: 452, Points: 68\nFinal Grade: B+","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768128058,\"networkid\":1}]}","768128058","","2026-07-26 02:28:36","2026-07-26 02:28:36","");
INSERT INTO `sms_logs` VALUES ("22","1","0745959757","","parent","Test SMS from Kenya EduHub - Grade: B+","general","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768128374,\"networkid\":1}]}","768128374","","2026-07-26 02:29:49","2026-07-26 02:29:49","");
INSERT INTO `sms_logs` VALUES ("23","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A-)\nCHEMISTRY: 70.00 (A)\nENGLISH: 55.00 (C+)\nGEOGRAPHY: 60.00 (B+)\nHISTORY AND GOVERNMENT: 80.00 (A)\nKISWAHILI: 60.00 (B-)\nMATHEMATICS: 50.00 (B-)\nPHYSICS: 50.00 (B+)\n\nTotal: 452, Points: 68\nFinal Grade: B+","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768128461,\"networkid\":1}]}","768128461","","2026-07-26 02:30:25","2026-07-26 02:30:25","");
INSERT INTO `sms_logs` VALUES ("24","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A-)\nCHEMISTRY: 70.00 (A)\nENGLISH: 55.00 (CPlus)\nGEOGRAPHY: 60.00 (BPlus)\nHISTORY AND GOVERNMENT: 80.00 (A)\nKISWAHILI: 60.00 (B-)\nMATHEMATICS: 50.00 (B-)\nPHYSICS: 50.00 (BPlus)\n\nTotal: 452, Points: 68\nFinal Grade: BPlus","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768128932,\"networkid\":1}]}","768128932","","2026-07-26 02:33:06","2026-07-26 02:33:06","");
INSERT INTO `sms_logs` VALUES ("25","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A (MINUS))\nCHEMISTRY: 70.00 (A (PLAIN))\nENGLISH: 55.00 (C (PLUS))\nGEOGRAPHY: 60.00 (B (PLUS))\nHISTORY AND GOVERNMENT: 80.00 (A (PLAIN))\nKISWAHILI: 60.00 (B (MINUS))\nMATHEMATICS: 50.00 (B (MINUS))\nPHYSICS: 50.00 (B (PLUS))\n\nTotal: 452, Points: 68\nFinal Grade: B (PLUS)","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768129978,\"networkid\":1}]}","768129978","","2026-07-26 02:43:38","2026-07-26 02:43:38","");
INSERT INTO `sms_logs` VALUES ("26","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A (MINUS))\nCHEMISTRY: 70.00 (A (PLAIN))\nENGLISH: 55.00 (C (PLUS))\nGEOGRAPHY: 60.00 (B (PLUS))\nHISTORY AND GOVERNMENT: 80.00 (A (PLAIN))\nKISWAHILI: 60.00 (B (MINUS))\nMATHEMATICS: 50.00 (B (MINUS))\nPHYSICS: 50.00 (B (PLUS))\n\nTotal: 452, Points: 68\nFinal Grade: B (PLUS)","results","mobitech","","sent","[{\"status_code\":\"1006\",\"status_desc\":\"Invalid credentials\",\"message_id\":\"0\",\"mobile_number\":\"254745959757\",\"network_id\":\"\",\"message_cost\":\"\",\"credit_balance\":\"\"}]","","","2026-07-26 02:47:50","2026-07-26 02:47:50","");
INSERT INTO `sms_logs` VALUES ("27","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A (MINUS))\nCHEMISTRY: 70.00 (A (PLAIN))\nENGLISH: 55.00 (C (PLUS))\nGEOGRAPHY: 60.00 (B (PLUS))\nHISTORY AND GOVERNMENT: 80.00 (A (PLAIN))\nKISWAHILI: 60.00 (B (MINUS))\nMATHEMATICS: 50.00 (B (MINUS))\nPHYSICS: 50.00 (B (PLUS))\n\nTotal: 452, Points: 68\nFinal Grade: B (PLUS)","results","mobitech","","sent","[{\"status_code\":\"1006\",\"status_desc\":\"Invalid credentials\",\"message_id\":\"0\",\"mobile_number\":\"254745959757\",\"network_id\":\"\",\"message_cost\":\"\",\"credit_balance\":\"\"}]","","","2026-07-26 02:48:18","2026-07-26 02:48:18","");
INSERT INTO `sms_logs` VALUES ("28","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A (MINUS))\nCHEMISTRY: 70.00 (A (PLAIN))\nENGLISH: 55.00 (C (PLUS))\nGEOGRAPHY: 60.00 (B (PLUS))\nHISTORY AND GOVERNMENT: 80.00 (A (PLAIN))\nKISWAHILI: 60.00 (B (MINUS))\nMATHEMATICS: 50.00 (B (MINUS))\nPHYSICS: 50.00 (B (PLUS))\n\nTotal: 452, Points: 68\nFinal Grade: B (PLUS)","results","mobitech","","sent","[{\"status_code\":\"1006\",\"status_desc\":\"Invalid credentials\",\"message_id\":\"0\",\"mobile_number\":\"254745959757\",\"network_id\":\"\",\"message_cost\":\"\",\"credit_balance\":\"\"}]","","","2026-07-26 02:49:24","2026-07-26 02:49:24","");
INSERT INTO `sms_logs` VALUES ("29","1","0745959757","","parent","Test SMS from Kenya EduHub via Mobitech","general","mobitech","","sent","[{\"status_code\":\"1006\",\"status_desc\":\"Invalid credentials\",\"message_id\":\"0\",\"mobile_number\":\"254745959757\",\"network_id\":\"\",\"message_cost\":\"\",\"credit_balance\":\"\"}]","","","2026-07-26 02:51:08","2026-07-26 02:51:08","");
INSERT INTO `sms_logs` VALUES ("30","1","0745959757","","parent","Test SMS from Kenya EduHub via Mobitech (Fixed)","general","mobitech","","sent","{\"status_code\":\"1000\",\"status_desc\":\"Success\",\"message_id\":31404455,\"mobile_number\":\"254745959757\",\"network_id\":\"1\",\"message_cost\":0.35,\"credit_balance\":19.15}","31404455","","2026-07-26 02:53:51","2026-07-26 02:53:51","");
INSERT INTO `sms_logs` VALUES ("31","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A (MINUS))\nCHEMISTRY: 70.00 (A (PLAIN))\nENGLISH: 55.00 (C (PLUS))\nGEOGRAPHY: 60.00 (B (PLUS))\nHISTORY AND GOVERNMENT: 80.00 (A (PLAIN))\nKISWAHILI: 60.00 (B (MINUS))\nMATHEMATICS: 50.00 (B (MINUS))\nPHYSICS: 50.00 (B (PLUS))\n\nTotal: 452, Points: 68\nFinal Grade: B (PLUS)","results","mobitech","","sent","{\"status_code\":\"1000\",\"status_desc\":\"Success\",\"message_id\":31404456,\"mobile_number\":\"254745959757\",\"network_id\":\"1\",\"message_cost\":1.0499999999999998,\"credit_balance\":18.099999999999998}","31404456","","2026-07-26 02:55:14","2026-07-26 02:55:14","");
INSERT INTO `sms_logs` VALUES ("32","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A (MINUS))\nCHEMISTRY: 70.00 (A (PLAIN))\nENGLISH: 55.00 (C (PLUS))\nGEOGRAPHY: 60.00 (B (PLUS))\nHISTORY AND GOVERNMENT: 80.00 (A (PLAIN))\nKISWAHILI: 60.00 (B (MINUS))\nMATHEMATICS: 50.00 (B (MINUS))\nPHYSICS: 50.00 (B (PLUS))\n\nTotal: 452, Points: 68\nFinal Grade: B (PLUS)","results","mobitech","","sent","{\"status_code\":\"1000\",\"status_desc\":\"Success\",\"message_id\":31404457,\"mobile_number\":\"254745959757\",\"network_id\":\"1\",\"message_cost\":1.0499999999999998,\"credit_balance\":17.05}","31404457","","2026-07-26 03:56:19","2026-07-26 03:56:19","");
INSERT INTO `sms_logs` VALUES ("33","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Fee Balance for BRIAN ONYANGO OTIENO (NDS/1)\nPeriod: Term 1 2026\nTotal Fees: KES 22,000\nAmount Paid: KES 10,020\nOutstanding Balance: KES 11,980\nPlease clear the balance to avoid penalties.","fee_balance","mobitech","","sent","{\"status_code\":\"1000\",\"status_desc\":\"Success\",\"message_id\":31404459,\"mobile_number\":\"254745959757\",\"network_id\":\"1\",\"message_cost\":0.7,\"credit_balance\":16.35}","31404459","","2026-07-26 04:27:51","2026-07-26 04:27:51","");
INSERT INTO `sms_logs` VALUES ("34","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Fee Balance for BRIAN ONYANGO OTIENO (NDS/1)\nPeriod: Term 2 2026\nFee Breakdown:\n- Tuition: KES 14,999 (Paid: 1)\nTotal Outstanding: KES 14,999\nPlease clear the balance to avoid penalties.","fee_balance","mobitech","","sent","{\"status_code\":\"1000\",\"status_desc\":\"Success\",\"message_id\":31404460,\"mobile_number\":\"254745959757\",\"network_id\":\"1\",\"message_cost\":0.7,\"credit_balance\":15.650000000000002}","31404460","","2026-07-26 04:32:03","2026-07-26 04:32:03","");
INSERT INTO `sms_logs` VALUES ("35","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Fee Balance for BRIAN ONYANGO OTIENO (NDS/1)\nPeriod: Term 2 2026\nFee Breakdown:\n- Tuition: KES 14,999 (Paid: 1)\nTotal Outstanding: KES 14,999\nPlease clear the balance to avoid penalties.","fee_balance","mobitech","","sent","{\"status_code\":\"1000\",\"status_desc\":\"Success\",\"message_id\":31404461,\"mobile_number\":\"254745959757\",\"network_id\":\"1\",\"message_cost\":0.7,\"credit_balance\":14.950000000000001}","31404461","","2026-07-26 04:33:07","2026-07-26 04:33:07","");
INSERT INTO `sms_logs` VALUES ("36","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Fee Balance for BRIAN ONYANGO OTIENO (NDS/1)\nPeriod: Term 2 2026\nFee Breakdown:\n- Tuition: KES 14,999 (Paid: 1)\nTotal Outstanding: KES 14,999\nPlease clear the balance to avoid penalties.","fee_balance","mobitech","","sent","{\"status_code\":\"1000\",\"status_desc\":\"Success\",\"message_id\":31404462,\"mobile_number\":\"254745959757\",\"network_id\":\"1\",\"message_cost\":0.7,\"credit_balance\":14.25}","31404462","","2026-07-26 04:41:22","2026-07-26 04:41:22","");
INSERT INTO `sms_logs` VALUES ("37","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Fee Balance for BRIAN ONYANGO OTIENO (NDS/1)\nPeriod: Term 2 2026\nTotal Fees: KES 15,000\nAmount Paid: KES 1\nOutstanding Balance: KES 14,999\nPlease clear the balance to avoid penalties.","fee_balance","mobitech","","sent","{\"status_code\":\"1000\",\"status_desc\":\"Success\",\"message_id\":31404463,\"mobile_number\":\"254745959757\",\"network_id\":\"1\",\"message_cost\":0.7,\"credit_balance\":13.55}","31404463","","2026-07-26 04:43:22","2026-07-26 04:43:22","");
INSERT INTO `sms_logs` VALUES ("38","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Fee Balance for BRIAN ONYANGO OTIENO (NDS/1)\nPeriod: Term 1 2026\nTotal Fees: KES 22,000\nAmount Paid: KES 10,020\nOutstanding Balance: KES 11,980\nPlease clear the balance to avoid penalties.","fee_balance","mobitech","","sent","{\"status_code\":\"1000\",\"status_desc\":\"Success\",\"message_id\":31404465,\"mobile_number\":\"254745959757\",\"network_id\":\"1\",\"message_cost\":0.7,\"credit_balance\":12.850000000000001}","31404465","","2026-07-26 04:51:53","2026-07-26 04:51:53","");
INSERT INTO `sms_logs` VALUES ("39","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Fee Balance for BRIAN ONYANGO OTIENO (NDS/1)\nPeriod: Term 1 2026\nTotal Fees: KES 22,000\nAmount Paid: KES 10,020\nOutstanding Balance: KES 11,980\nPlease clear the balance to avoid penalties.","fee_balance","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768142932,\"networkid\":1}]}","768142932","","2026-07-26 04:53:57","2026-07-26 04:53:57","");
INSERT INTO `sms_logs` VALUES ("40","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Fee Balance for BRIAN ONYANGO OTIENO (NDS/1)\nPeriod: Term 1 2026\nTotal Fees: KES 22,000\nAmount Paid: KES 10,020\nOutstanding Balance: KES 11,980\nPlease clear the balance to avoid penalties.","fee_balance","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768143121,\"networkid\":1}]}","768143121","","2026-07-26 04:55:41","2026-07-26 04:55:41","");
INSERT INTO `sms_logs` VALUES ("41","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Fee Balance for BRIAN ONYANGO OTIENO (NDS/1)\nPeriod: Term 1 2026\nTotal Fees: KES 22,000\nAmount Paid: KES 10,020\nOutstanding Balance: KES 11,980\nPlease clear the balance to avoid penalties.","fee_balance","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768144544,\"networkid\":1}]}","768144544","","2026-07-26 05:03:36","2026-07-26 05:03:36","");
INSERT INTO `sms_logs` VALUES ("42","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Fee Balance for BRIAN ONYANGO OTIENO (NDS/1)\nPeriod: Term 1 2026\nTotal Fees: KES 22,000\nAmount Paid: KES 10,020\nOutstanding Balance: KES 11,980\nPlease clear the balance to avoid penalties.","fee_balance","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768144730,\"networkid\":1}]}","768144730","","2026-07-26 05:05:57","2026-07-26 05:05:57","");
INSERT INTO `sms_logs` VALUES ("43","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Fee Balance for BRIAN ONYANGO OTIENO (NDS/1)\nPeriod: Term 1 2026\nTotal Fees: KES 22,000\nAmount Paid: KES 10,020\nOutstanding Balance: KES 11,980\nPlease clear the balance to avoid penalties.","fee_balance","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768144849,\"networkid\":1}]}","768144849","","2026-07-26 05:07:22","2026-07-26 05:07:22","");
INSERT INTO `sms_logs` VALUES ("44","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Fee Balance for BRIAN ONYANGO OTIENO (NDS/1)\nPeriod: Term 1 2026\nTotal Fees: KES 1,000\nAmount Paid: KES 1\nOutstanding Balance: KES 999\nPlease clear the balance to avoid penalties.","fee_balance","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768145064,\"networkid\":1}]}","768145064","","2026-07-26 05:10:02","2026-07-26 05:10:02","");
INSERT INTO `sms_logs` VALUES ("45","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Fee Balance for BRIAN ONYANGO OTIENO (NDS/1)\nPeriod: Term 1 2026\nTotal Fees: KES 21,000\nAmount Paid: KES 10,019\nOutstanding Balance: KES 10,981\nPlease clear the balance to avoid penalties.","fee_balance","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768145104,\"networkid\":1}]}","768145104","","2026-07-26 05:10:18","2026-07-26 05:10:18","");
INSERT INTO `sms_logs` VALUES ("46","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Fee Balance for BRIAN ONYANGO OTIENO (NDS/1)\nPeriod: Term 2 2026\nTotal Fees: KES 15,000\nAmount Paid: KES 1\nOutstanding Balance: KES 14,999\nPlease clear the balance to avoid penalties.","fee_balance","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768145149,\"networkid\":1}]}","768145149","","2026-07-26 05:10:54","2026-07-26 05:10:54","");
INSERT INTO `sms_logs` VALUES ("47","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Fee Balance for BRIAN ONYANGO OTIENO (NDS/1)\nPeriod: Term 1 2026\nTotal Fees: KES 21,000\nAmount Paid: KES 10,019\nOutstanding Balance: KES 10,981\nPlease clear the balance to avoid penalties.","fee_balance","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":768811249,\"networkid\":1}]}","768811249","","2026-07-26 14:39:18","2026-07-26 14:39:18","");
INSERT INTO `sms_logs` VALUES ("48","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A (MINUS))\nCHEMISTRY: 70.00 (A (PLAIN))\nENGLISH: 55.00 (C (PLUS))\nGEOGRAPHY: 60.00 (B (PLUS))\nHISTORY AND GOVERNMENT: 80.00 (A (PLAIN))\nKISWAHILI: 60.00 (B (MINUS))\nMATHEMATICS: 50.00 (B (MINUS))\nPHYSICS: 50.00 (B (PLUS))\n\nTotal: 452, Points: 68\nFinal Grade: B (PLUS)","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":770814936,\"networkid\":1}]}","770814936","","2026-07-27 16:10:47","2026-07-27 16:10:47","");
INSERT INTO `sms_logs` VALUES ("49","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A (MINUS))\nCHEMISTRY: 70.00 (A (PLAIN))\nENGLISH: 55.00 (C (PLUS))\nGEOGRAPHY: 60.00 (B (PLUS))\nHISTORY AND GOVERNMENT: 80.00 (A (PLAIN))\nKISWAHILI: 60.00 (B (MINUS))\nMATHEMATICS: 50.00 (B (MINUS))\nPHYSICS: 50.00 (B (PLUS))\n\nTotal: 452, Points: 68\nFinal Grade: B (PLUS)","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":774327117,\"networkid\":1}]}","774327117","","2026-07-29 01:26:46","2026-07-29 01:26:46","");
INSERT INTO `sms_logs` VALUES ("50","1","0704431844","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A (MINUS))\nCHEMISTRY: 70.00 (A (PLAIN))\nENGLISH: 55.00 (C (PLUS))\nGEOGRAPHY: 60.00 (B (PLUS))\nHISTORY AND GOVERNMENT: 80.00 (A (PLAIN))\nKISWAHILI: 60.00 (B (MINUS))\nMATHEMATICS: 50.00 (B (MINUS))\nPHYSICS: 50.00 (B (PLUS))\n\nTotal: 452, Points: 68\nFinal Grade: B (PLUS)","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254704431844\",\"messageid\":774825582,\"networkid\":1}]}","774825582","","2026-07-29 09:31:26","2026-07-29 09:31:26","");
INSERT INTO `sms_logs` VALUES ("51","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A (MINUS))\nCHEMISTRY: 70.00 (A (PLAIN))\nENGLISH: 55.00 (C (PLUS))\nGEOGRAPHY: 60.00 (B (PLUS))\nHISTORY AND GOVERNMENT: 80.00 (A (PLAIN))\nKISWAHILI: 60.00 (B (MINUS))\nMATHEMATICS: 50.00 (B (MINUS))\nPHYSICS: 50.00 (B (PLUS))\n\nTotal: 452, Points: 68\nFinal Grade: B (PLUS)","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":774827975,\"networkid\":1}]}","774827975","","2026-07-29 09:33:41","2026-07-29 09:33:41","");
INSERT INTO `sms_logs` VALUES ("52","1","0791792148","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A (MINUS))\nCHEMISTRY: 70.00 (A (PLAIN))\nENGLISH: 55.00 (C (PLUS))\nGEOGRAPHY: 60.00 (B (PLUS))\nHISTORY AND GOVERNMENT: 80.00 (A (PLAIN))\nKISWAHILI: 60.00 (B (MINUS))\nMATHEMATICS: 50.00 (B (MINUS))\nPHYSICS: 50.00 (B (PLUS))\n\nTotal: 452, Points: 68\nFinal Grade: B (PLUS)","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254791792148\",\"messageid\":774831349,\"networkid\":1}]}","774831349","","2026-07-29 09:35:59","2026-07-29 09:35:59","");
INSERT INTO `sms_logs` VALUES ("53","1","0704431844","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A (MINUS))\nCHEMISTRY: 70.00 (A (PLAIN))\nENGLISH: 55.00 (C (PLUS))\nGEOGRAPHY: 60.00 (B (PLUS))\nHISTORY AND GOVERNMENT: 80.00 (A (PLAIN))\nKISWAHILI: 60.00 (B (MINUS))\nMATHEMATICS: 50.00 (B (MINUS))\nPHYSICS: 50.00 (B (PLUS))\n\nTotal: 452, Points: 68\nFinal Grade: B (PLUS)","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254704431844\",\"messageid\":774833270,\"networkid\":1}]}","774833270","","2026-07-29 09:36:47","2026-07-29 09:36:47","");
INSERT INTO `sms_logs` VALUES ("54","1","0704431844","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Fee Balance for BRIAN ONYANGO OTIENO (NDS/1)\nPeriod: Term 2 2026\nTotal Fees: KES 15,000\nAmount Paid: KES 4\nOutstanding Balance: KES 14,996\nPlease clear the balance to avoid penalties.","fee_balance","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254704431844\",\"messageid\":774856948,\"networkid\":1}]}","774856948","","2026-07-29 09:43:29","2026-07-29 09:43:29","");
INSERT INTO `sms_logs` VALUES ("55","1","0791792148","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A (MINUS))\nCHEMISTRY: 70.00 (A (PLAIN))\nENGLISH: 55.00 (C (PLUS))\nGEOGRAPHY: 60.00 (B (PLUS))\nHISTORY AND GOVERNMENT: 80.00 (A (PLAIN))\nKISWAHILI: 60.00 (B (MINUS))\nMATHEMATICS: 50.00 (B (MINUS))\nPHYSICS: 50.00 (B (PLUS))\n\nTotal: 452, Points: 68\nFinal Grade: B (PLUS)","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254791792148\",\"messageid\":776909361,\"networkid\":1}]}","776909361","","2026-07-30 06:20:41","2026-07-30 06:20:41","");
INSERT INTO `sms_logs` VALUES ("56","1","0791792148","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A (MINUS))\nCHEMISTRY: 70.00 (A (PLAIN))\nENGLISH: 55.00 (C (PLUS))\nGEOGRAPHY: 60.00 (B (PLUS))\nHISTORY AND GOVERNMENT: 80.00 (A (PLAIN))\nKISWAHILI: 60.00 (B (MINUS))\nMATHEMATICS: 50.00 (B (MINUS))\nPHYSICS: 50.00 (B (PLUS))\n\nTotal: 452, Points: 68\nFinal Grade: B (PLUS)","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254791792148\",\"messageid\":779864510,\"networkid\":1}]}","779864510","","2026-07-31 12:08:26","2026-07-31 12:08:26","");
INSERT INTO `sms_logs` VALUES ("57","1","0791792148","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A (MINUS))\nCHEMISTRY: 70.00 (A (PLAIN))\nENGLISH: 55.00 (C (PLUS))\nGEOGRAPHY: 60.00 (B (PLUS))\nHISTORY AND GOVERNMENT: 80.00 (A (PLAIN))\nKISWAHILI: 60.00 (B (MINUS))\nMATHEMATICS: 50.00 (B (MINUS))\nPHYSICS: 50.00 (B (PLUS))\n\nTotal: 452, Points: 68\nFinal Grade: B (PLUS)","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254791792148\",\"messageid\":780571414,\"networkid\":1}]}","780571414","","2026-07-31 18:31:42","2026-07-31 18:31:42","");
INSERT INTO `sms_logs` VALUES ("58","1","0791792148","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Fee Balance for BRIAN ONYANGO OTIENO (NDS/1)\nPeriod: Term 2 2026\nTotal Fees: KES 15,000\nAmount Paid: KES 4\nOutstanding Balance: KES 14,996\nPlease clear the balance to avoid penalties.","fee_balance","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254791792148\",\"messageid\":780571963,\"networkid\":1}]}","780571963","","2026-07-31 18:32:13","2026-07-31 18:32:13","");
INSERT INTO `sms_logs` VALUES ("59","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A (MINUS))\nCHEMISTRY: 70.00 (A (PLAIN))\nENGLISH: 55.00 (C (PLUS))\nGEOGRAPHY: 60.00 (B (PLUS))\nHISTORY AND GOVERNMENT: 80.00 (A (PLAIN))\nKISWAHILI: 60.00 (B (MINUS))\nMATHEMATICS: 50.00 (B (MINUS))\nPHYSICS: 50.00 (B (PLUS))\n\nTotal: 452, Points: 68\nFinal Grade: B (PLUS)","results","textsms","","failed","[]","","","2026-08-01 14:35:36","2026-08-01 14:35:36","");
INSERT INTO `sms_logs` VALUES ("60","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A (MINUS))\nCHEMISTRY: 70.00 (A (PLAIN))\nENGLISH: 55.00 (C (PLUS))\nGEOGRAPHY: 60.00 (B (PLUS))\nHISTORY AND GOVERNMENT: 80.00 (A (PLAIN))\nKISWAHILI: 60.00 (B (MINUS))\nMATHEMATICS: 50.00 (B (MINUS))\nPHYSICS: 50.00 (B (PLUS))\n\nTotal: 452, Points: 68\nFinal Grade: B (PLUS)","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":782010076,\"networkid\":1}]}","782010076","","2026-08-01 14:35:50","2026-08-01 14:35:50","");
INSERT INTO `sms_logs` VALUES ("61","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - END TERM Results for BRIAN ONYANGO OTIENO (NDS/1)\n\nBIOLOGY: 77.00 (A (MINUS))\nCHEMISTRY: 70.00 (A (PLAIN))\nENGLISH: 55.00 (C (PLUS))\nGEOGRAPHY: 60.00 (B (PLUS))\nHISTORY AND GOVERNMENT: 80.00 (A (PLAIN))\nKISWAHILI: 60.00 (B (MINUS))\nMATHEMATICS: 50.00 (B (MINUS))\nPHYSICS: 50.00 (B (PLUS))\n\nTotal: 452, Points: 68\nFinal Grade: B (PLUS)","results","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":782433249,\"networkid\":1}]}","782433249","","2026-08-01 18:29:15","2026-08-01 18:29:15","");
INSERT INTO `sms_logs` VALUES ("62","1","0745959757","SAMUEL OKECH","parent","NDERE SENIOR SCHOOL - Fee Balance for BRIAN ONYANGO OTIENO (NDS/1)\nPeriod: Term 2 2026\nTotal Fees: KES 15,000\nAmount Paid: KES 14\nOutstanding Balance: KES 14,986\nPlease clear the balance to avoid penalties.","fee_balance","textsms","","sent","{\"responses\":[{\"response-code\":200,\"response-description\":\"Success\",\"mobile\":\"254745959757\",\"messageid\":782433494,\"networkid\":1}]}","782433494","","2026-08-01 18:29:50","2026-08-01 18:29:50","");


-- Table structure for `smtp_settings`
DROP TABLE IF EXISTS `smtp_settings`;
CREATE TABLE `smtp_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `app_password` varchar(255) NOT NULL,
  `smtp_host` varchar(255) DEFAULT 'smtp.gmail.com',
  `smtp_port` int(11) DEFAULT 587,
  `encryption` varchar(10) DEFAULT 'tls',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_school_email` (`school_id`,`email`),
  CONSTRAINT `smtp_settings_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `smtp_settings` VALUES ("1","1","otienobrian029@gmail.com","dwuunoftzkodeome","smtp.gmail.com","587","tls","2026-07-19 19:05:06","2026-07-19 19:05:08");


-- Table structure for `streams`
DROP TABLE IF EXISTS `streams`;
CREATE TABLE `streams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `stream_name` varchar(50) NOT NULL,
  `capacity` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_class_id` (`class_id`),
  CONSTRAINT `fk_streams_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `streams` VALUES ("1","1","EAST","80","2026-07-04 18:11:19");
INSERT INTO `streams` VALUES ("2","1","WEST","80","2026-07-04 18:55:18");
INSERT INTO `streams` VALUES ("3","1","NORTH","80","2026-07-04 18:55:40");
INSERT INTO `streams` VALUES ("4","1","SOUTH","80","2026-07-04 18:55:55");
INSERT INTO `streams` VALUES ("5","2","EAST","80","2026-07-05 22:02:20");
INSERT INTO `streams` VALUES ("6","2","WEST","80","2026-07-05 22:02:51");
INSERT INTO `streams` VALUES ("7","2","NORTH","80","2026-07-05 22:03:20");
INSERT INTO `streams` VALUES ("8","2","SOUTH","80","2026-07-05 22:04:04");


-- Table structure for `student_parents`
DROP TABLE IF EXISTS `student_parents`;
CREATE TABLE `student_parents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_parent_id` (`parent_id`),
  CONSTRAINT `fk_sp_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sp_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `student_parents` VALUES ("1","4","2","1","2026-07-04 18:52:29");


-- Table structure for `student_subjects`
DROP TABLE IF EXISTS `student_subjects`;
CREATE TABLE `student_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `stream_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_subject` (`student_id`,`subject_id`),
  KEY `student_id` (`student_id`),
  KEY `subject_id` (`subject_id`),
  KEY `school_id` (`school_id`),
  KEY `class_id` (`class_id`),
  KEY `stream_id` (`stream_id`),
  CONSTRAINT `student_subjects_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_subjects_school_fk` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_subjects_stream_fk` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_subjects_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_subjects_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `student_subjects` VALUES ("3","4","6","1","","","2026-07-25 19:15:44","2026-07-25 19:15:44");
INSERT INTO `student_subjects` VALUES ("4","4","11","1","","","2026-07-25 19:15:44","2026-07-25 19:15:44");
INSERT INTO `student_subjects` VALUES ("5","4","12","1","","","2026-07-25 19:15:44","2026-07-25 19:15:44");
INSERT INTO `student_subjects` VALUES ("6","4","2","1","","","2026-07-25 19:18:08","2026-07-25 19:18:08");
INSERT INTO `student_subjects` VALUES ("7","4","5","1","","","2026-07-25 19:18:54","2026-07-25 19:18:54");
INSERT INTO `student_subjects` VALUES ("9","4","10","1","","","2026-07-25 19:28:56","2026-07-25 19:28:56");
INSERT INTO `student_subjects` VALUES ("10","4","4","1","","","2026-07-25 19:29:44","2026-07-25 19:29:44");
INSERT INTO `student_subjects` VALUES ("11","4","3","1","","","2026-07-25 19:30:54","2026-07-25 19:30:54");


-- Table structure for `students`
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_admission` (`school_id`,`admission_number`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_class_id` (`class_id`),
  KEY `idx_stream_id` (`stream_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_students_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_students_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_students_stream` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `students` VALUES ("4","1","NDS/1","","BRIAN ONYANGO","OTIENO","Male","2002-10-15","1","1","2026-07-04","active","","2026-07-04 18:49:24","2026-07-04 18:49:24");


-- Table structure for `subject_categories`
DROP TABLE IF EXISTS `subject_categories`;
CREATE TABLE `subject_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_code` varchar(20) NOT NULL,
  `is_compulsory` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `subject_categories_school_fk` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `subject_categories` VALUES ("1","1","Compulsory","1","1","2026-07-25 19:02:43","2026-07-25 19:02:43");
INSERT INTO `subject_categories` VALUES ("2","1","Sciences","2","0","2026-07-25 19:02:43","2026-07-25 19:02:43");
INSERT INTO `subject_categories` VALUES ("3","1","Humanities","3","0","2026-07-25 19:02:43","2026-07-25 19:02:43");
INSERT INTO `subject_categories` VALUES ("4","1","Technical","4","0","2026-07-25 19:02:43","2026-07-25 19:02:43");


-- Table structure for `subjects`
DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_status` (`status`),
  KEY `fk_subjects_category` (`category_id`),
  CONSTRAINT `fk_subjects_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `subjects` VALUES ("2","1","2","PHYSICS","001","active","2026-07-04 20:09:30");
INSERT INTO `subjects` VALUES ("3","1","1","CHEMISTRY","002","active","2026-07-05 22:04:39");
INSERT INTO `subjects` VALUES ("4","1","2","BIOLOGY","003","active","2026-07-05 22:05:06");
INSERT INTO `subjects` VALUES ("5","1","3","GEOGRAPHY","004","active","2026-07-05 22:05:32");
INSERT INTO `subjects` VALUES ("6","1","1","MATHEMATICS","005","active","2026-07-05 22:06:29");
INSERT INTO `subjects` VALUES ("7","1","3","CRE","006","active","2026-07-05 22:07:07");
INSERT INTO `subjects` VALUES ("8","1","4","AGRICULTURE","007","active","2026-07-05 22:07:25");
INSERT INTO `subjects` VALUES ("9","1","4","BUSINESSES STUDIES","008","active","2026-07-05 22:08:04");
INSERT INTO `subjects` VALUES ("10","1","3","HISTORY AND GOVERNMENT","009","active","2026-07-05 22:08:37");
INSERT INTO `subjects` VALUES ("11","1","1","ENGLISH","010","active","2026-07-05 22:09:13");
INSERT INTO `subjects` VALUES ("12","1","1","KISWAHILI","011","active","2026-07-05 22:09:42");


-- Table structure for `system_revenue`
DROP TABLE IF EXISTS `system_revenue`;
CREATE TABLE `system_revenue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revenue_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `source_school_id` int(11) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_revenue_type` (`revenue_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for `teacher_logins`
DROP TABLE IF EXISTS `teacher_logins`;
CREATE TABLE `teacher_logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_teacher_id` (`teacher_id`),
  CONSTRAINT `fk_teacher_login_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `teacher_logins` VALUES ("1","1","otienobrian029@gmail.com","$2y$10$gYVB/ChfPTZDykMAz2VCc.RW5M1ckMchJgs5F3DjISJ7uo6j.kb0W","1","2026-07-04 19:46:26");


-- Table structure for `teacher_sessions`
DROP TABLE IF EXISTS `teacher_sessions`;
CREATE TABLE `teacher_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_teacher_session_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `teacher_sessions` VALUES ("1","1","e8fd7e9d0472afeb0228de31d5c9dfc2b697116120ffa3be7d56981f9464d469","2026-07-05 02:47:25","2026-07-04 19:47:25");
INSERT INTO `teacher_sessions` VALUES ("2","1","2718252a48e0315d61eb0de28882649c592ec250459ce787633607d1bb31b340","2026-07-05 05:27:34","2026-07-04 22:27:34");
INSERT INTO `teacher_sessions` VALUES ("3","1","a051b3c59f39613b6562808da8f371080e14fdb6f8048227cbeec1eff4001c3f","2026-07-05 08:45:32","2026-07-05 01:45:32");
INSERT INTO `teacher_sessions` VALUES ("4","1","650d1b32102b72945587c1cf668d8a6860c602e58df70b231e9d1d63caab1fa5","2026-07-05 21:07:23","2026-07-05 14:07:23");
INSERT INTO `teacher_sessions` VALUES ("5","1","f3e2d07103d5608fb9ce77a4b175bfa92a6cce936f2f1ea2615f4c6b16639d0b","2026-07-05 23:27:01","2026-07-05 16:27:01");
INSERT INTO `teacher_sessions` VALUES ("6","1","d90c2dd32b19b3ee6ceaebfef7921cd00139c0030771e3c62a2f6d3038eac859","2026-07-06 02:05:17","2026-07-05 19:05:17");
INSERT INTO `teacher_sessions` VALUES ("7","1","c54a538025676f1f2a4c99646c86c9fbc7463736a121f412e4fbc4c0081aa34a","2026-07-06 22:11:29","2026-07-06 15:11:29");
INSERT INTO `teacher_sessions` VALUES ("8","1","35ef3f1d652ff2770ec3d583f66ef732db6ca8deb79f8d0772813b3e7bb6d0b7","2026-07-07 22:14:58","2026-07-07 15:14:58");
INSERT INTO `teacher_sessions` VALUES ("9","1","244b8feab075c772022b932ab01ed33523aaf927d78de99a1d65ce279ffa0f68","2026-07-08 05:22:53","2026-07-07 22:22:53");
INSERT INTO `teacher_sessions` VALUES ("10","1","9974285469aa64a2e477278def1884384a5ce6dc2a9ecc1c8875729d97b36b45","2026-07-08 18:24:38","2026-07-08 11:24:38");
INSERT INTO `teacher_sessions` VALUES ("11","1","de2da3e602c1d583e0bf23937eed58a9abc9ebdb8b0bed6c4b7911e615fae97b","2026-07-08 20:30:19","2026-07-08 13:30:19");
INSERT INTO `teacher_sessions` VALUES ("12","1","e08f0057bfd253828846b64604134136bcba2259fc2af1acf75dc3be34e79d6f","2026-07-09 05:17:29","2026-07-08 22:17:29");
INSERT INTO `teacher_sessions` VALUES ("13","1","58e57db3c6267d8a70a61f0195c73f2011864bc0b2dc6ec0b6682684bd8c8fdc","2026-07-14 01:53:33","2026-07-13 18:53:33");
INSERT INTO `teacher_sessions` VALUES ("19","1","dc41d450b00cac2297a0feb46d22c5d80a665626fd9061dc5719f0c8cc4a9442","2026-07-15 07:30:01","2026-07-15 00:30:01");
INSERT INTO `teacher_sessions` VALUES ("20","1","8381439a09251a9ec45a3eb41e63700b418265adf2ca160ae75a5b0d187e3950","2026-07-15 08:45:44","2026-07-15 01:45:44");
INSERT INTO `teacher_sessions` VALUES ("21","1","561aa07c65d3a78549ac43c50a3b017378ac5c38455721758e286bcbfa0ab8c9","2026-07-19 23:25:24","2026-07-19 16:25:24");
INSERT INTO `teacher_sessions` VALUES ("22","1","deabee5be13065ded1803bce07a55bf550569fdedbbd870785cd1e53b76263df","2026-07-21 07:20:33","2026-07-21 00:20:33");
INSERT INTO `teacher_sessions` VALUES ("23","1","0458d2922c38e9c6562987e829c35f10e90a88aa6a3c9d4b89aa98d5daa15242","2026-07-22 08:45:51","2026-07-22 01:45:51");
INSERT INTO `teacher_sessions` VALUES ("24","1","16dea596b6e67d2917af104426d745c01dae83371d42d5b9d749a6c21bc06947","2026-07-22 22:46:44","2026-07-22 15:46:44");
INSERT INTO `teacher_sessions` VALUES ("25","1","755871d607fc33be97fcf0ffef3ee2ed1ee3e55b48200ca4a5f9e9993edb85a3","2026-07-22 22:47:11","2026-07-22 15:47:11");
INSERT INTO `teacher_sessions` VALUES ("26","1","a592529500b207179143dd4bf8d656a1f535e4ce8ce096272f7cdebcdeeb41dd","2026-07-22 22:55:58","2026-07-22 15:55:58");
INSERT INTO `teacher_sessions` VALUES ("27","1","edcb913aa38f1a80cd27f72d6ce6261efd9199f29831e96fcddee052e49cacc1","2026-07-23 06:45:05","2026-07-22 23:45:05");
INSERT INTO `teacher_sessions` VALUES ("28","1","8bb29c3281a503ad93a00914f71dd7575b72477b33fcd658f0c9064239e56345","2026-07-24 02:00:20","2026-07-23 19:00:20");
INSERT INTO `teacher_sessions` VALUES ("29","1","c0261ef51759e8a9b09b19485046476f7b6a7a87a0dc18150a2f266bc245eedc","2026-07-25 05:43:19","2026-07-24 22:43:19");
INSERT INTO `teacher_sessions` VALUES ("30","1","02051bed88bc4b2f4f24c33527994c3424a41d7fe94d5c33e517b2b42c9a4ad6","2026-07-25 23:05:10","2026-07-25 16:05:10");
INSERT INTO `teacher_sessions` VALUES ("31","1","2bd4d62db11fb4bc8005f6795b1a84294572fdb0e467f16f8c65c90f1a2f9088","2026-07-25 23:43:59","2026-07-25 16:43:59");
INSERT INTO `teacher_sessions` VALUES ("32","1","b6a2405ceaf6efd81d3cf5c328275f51c1be60793e500d24c5ed88fab7297302","2026-07-25 23:48:38","2026-07-25 16:48:38");
INSERT INTO `teacher_sessions` VALUES ("33","1","4e78f11436b613b6571258eb5f54cc56f78ed51c2810baf4412fc25ef2324fc6","2026-07-26 08:49:25","2026-07-26 01:49:25");
INSERT INTO `teacher_sessions` VALUES ("34","1","19616157e21ce003c72537e32bb3fb072b0bcd21111c74de115dd83a9c10f98c","2026-07-26 09:26:25","2026-07-26 02:26:25");
INSERT INTO `teacher_sessions` VALUES ("35","1","b4153f7f004d5c3ec779fdcf53a5fc1bd880940363e8bfe93ba6d0a50430218b","2026-07-26 20:54:34","2026-07-26 13:54:34");
INSERT INTO `teacher_sessions` VALUES ("36","1","f942a413a1a3ca6fe08acaa94a5edfdf7b727cb213ce2359136f614f24ec48ce","2026-07-26 21:02:33","2026-07-26 14:02:33");
INSERT INTO `teacher_sessions` VALUES ("37","1","7f7f5b38c86fc91e7c162ea8e6a44cc45045346a58a539219602cbafedb28fc6","2026-07-29 10:27:40","2026-07-29 03:27:40");
INSERT INTO `teacher_sessions` VALUES ("38","1","a6db5690d171e299bcb5570f396cbc1d69acc8df3cd860e0149d6a9f3a6c43fa","2026-07-29 15:35:26","2026-07-29 08:35:26");
INSERT INTO `teacher_sessions` VALUES ("40","1","27c5c2526544a1f9955b2053d64ec4645d71e13f9e0876b1e1e28fd6bbde91a4","2026-07-31 19:17:45","2026-07-31 12:17:45");
INSERT INTO `teacher_sessions` VALUES ("41","1","2cef93a653280a5da6083ec1ea1684afdca8923183aa8cc5607b425b8116220a","2026-08-01 21:15:14","2026-08-01 14:15:14");


-- Table structure for `teacher_subjects`
DROP TABLE IF EXISTS `teacher_subjects`;
CREATE TABLE `teacher_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `subject` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_class_id` (`class_id`),
  KEY `idx_subject_id` (`subject_id`),
  CONSTRAINT `fk_teacher_subjects_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_teacher_subjects_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_teacher_subjects_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for `teachers`
DROP TABLE IF EXISTS `teachers`;
CREATE TABLE `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_email` (`email`),
  KEY `idx_class_id` (`class_id`),
  KEY `idx_stream_id` (`stream_id`),
  KEY `idx_teacher_type` (`teacher_type`),
  CONSTRAINT `fk_teachers_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_teachers_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_teachers_stream` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `teachers` VALUES ("1","1","1","1","class_teacher","ROBINSON","OMOLLO","otienobrian029@gmail.com","0745959757","40718992","Kisumu\n40100 kisumu","PHYSICS","active","2026-07-04 19:46:26");


-- Table structure for `terms`
DROP TABLE IF EXISTS `terms`;
CREATE TABLE `terms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `term_name` varchar(50) NOT NULL,
  `term_number` tinyint(4) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `status` enum('upcoming','active','completed','ended') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_school_year_term` (`school_id`,`year`,`term_name`),
  CONSTRAINT `terms_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `terms` VALUES ("2","1","2026","Term 2","2","2026-04-27","2026-07-31","1","active","2026-07-23 23:28:56","2026-07-23 23:28:59");
INSERT INTO `terms` VALUES ("3","1","2026","Term 1","1","2026-01-05","2026-04-03","0","upcoming","2026-07-23 23:59:02","2026-07-23 23:59:02");


-- Table structure for `timetable_assignments`
DROP TABLE IF EXISTS `timetable_assignments`;
CREATE TABLE `timetable_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timetable_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `slot_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `stream_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `slot_id` (`slot_id`),
  KEY `idx_timetable_id` (`timetable_id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_class_id` (`class_id`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_subject_id` (`subject_id`),
  CONSTRAINT `timetable_assignments_ibfk_1` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_assignments_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_assignments_ibfk_3` FOREIGN KEY (`slot_id`) REFERENCES `timetable_slots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_assignments_ibfk_4` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_assignments_ibfk_5` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_assignments_ibfk_6` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `timetable_assignments` VALUES ("2","3","1","12","1","1","2","1","Test","2026-07-23 21:03:47","2026-07-23 21:03:47");


-- Table structure for `timetable_slots`
DROP TABLE IF EXISTS `timetable_slots`;
CREATE TABLE `timetable_slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `year` int(11) NOT NULL DEFAULT year(curdate()),
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `break_type` enum('none','short_break','lunch_break','recess') DEFAULT 'none',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_slot` (`school_id`,`day_of_week`,`start_time`,`end_time`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_day_of_week` (`day_of_week`),
  CONSTRAINT `timetable_slots_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `timetable_slots` VALUES ("12","1","2026","Monday","08:00:00","09:59:00","none","2026-07-23 21:02:23");
INSERT INTO `timetable_slots` VALUES ("13","1","2026","Tuesday","08:00:00","09:59:00","none","2026-07-23 21:02:23");
INSERT INTO `timetable_slots` VALUES ("14","1","2026","Wednesday","08:00:00","09:59:00","none","2026-07-23 21:02:23");
INSERT INTO `timetable_slots` VALUES ("15","1","2026","Thursday","08:00:00","09:59:00","none","2026-07-23 21:02:23");
INSERT INTO `timetable_slots` VALUES ("16","1","2026","Friday","08:00:00","09:59:00","none","2026-07-23 21:02:23");


-- Table structure for `timetables`
DROP TABLE IF EXISTS `timetables`;
CREATE TABLE `timetables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `year` int(11) NOT NULL DEFAULT year(curdate()),
  `name` varchar(255) NOT NULL,
  `timetable_type` enum('weekly','daily','exam') DEFAULT 'weekly',
  `term` varchar(50) NOT NULL,
  `class_id` int(11) NOT NULL,
  `status` enum('draft','active','archived') DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_class_id` (`class_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `timetables_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetables_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `timetables` VALUES ("3","1","2026","TEST","weekly","Term 1","1","draft","1","2026-07-23 21:03:10","2026-07-23 21:03:10");


-- Table structure for `transaction_fees`
DROP TABLE IF EXISTS `transaction_fees`;
CREATE TABLE `transaction_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `transaction_amount` decimal(10,2) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL,
  `fee_rate` decimal(10,4) NOT NULL,
  `rate_type` varchar(20) NOT NULL,
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_transaction_type` (`transaction_type`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for `transaction_rates`
DROP TABLE IF EXISTS `transaction_rates`;
CREATE TABLE `transaction_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_type` varchar(50) NOT NULL,
  `rate_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `rate_value` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `min_fee` decimal(10,2) DEFAULT 0.00,
  `max_fee` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_type` (`transaction_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for `transactions`
DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `MerchantRequestID` varchar(500) NOT NULL,
  `CheckoutRequestID` varchar(500) NOT NULL,
  `ResultCode` varchar(500) NOT NULL,
  `ResultDesc` varchar(500) NOT NULL,
  `Amount` int(11) NOT NULL,
  `MpesaReceiptNumber` varchar(500) NOT NULL,
  `PhoneNumber` varchar(500) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  KEY `idx_checkout_request_id` (`CheckoutRequestID`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_phone` (`PhoneNumber`)
) ENGINE=InnoDB AUTO_INCREMENT=250 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `transactions` VALUES ("1","TEST-1777658383-1","ws_CO_1777658383_123456","0","","100","TEST123456","254745959757","1","2026-05-01 20:59:43");
INSERT INTO `transactions` VALUES ("2","TEST-1777658408-1","ws_CO_1777658408_123456","0","","100","TEST123456","254745959757","1","2026-05-01 21:00:08");
INSERT INTO `transactions` VALUES ("3","TEST-1777658523-1","ws_CO_1777658523_123456","0","0","100","TEST123456","254745959757","1","2026-05-01 21:02:03");
INSERT INTO `transactions` VALUES ("4","TEST-1777658693-1","ws_CO_1777658693_123456","0","0","100","TEST123456","254745959757","1","2026-05-01 21:04:53");
INSERT INTO `transactions` VALUES ("5","TEST-1777658712-1","ws_CO_1777658712_123456","0","0","100","TEST123456","254745959757","1","2026-05-01 21:05:12");
INSERT INTO `transactions` VALUES ("6","TEST-1777658863-1","ws_CO_1777658863_123456","0","0","100","TEST123456","254745959757","1","2026-05-01 21:07:43");
INSERT INTO `transactions` VALUES ("7","TEST-1777659188-1","ws_CO_1777659188_123456","0","0","100","TEST123456","254745959757","1","2026-05-01 21:13:08");
INSERT INTO `transactions` VALUES ("8","TEST-1777659216-1","ws_CO_1777659216_123456","0","","100","TEST123456","254745959757","1","2026-05-01 21:13:36");
INSERT INTO `transactions` VALUES ("9","TEST-1777659885-1","ws_CO_1777659885_123456","0","The service request is processed successfully.","100","TEST123456","","0","2026-05-01 21:24:45");
INSERT INTO `transactions` VALUES ("10","","","","","0","","","0","2026-05-01 21:25:04");
INSERT INTO `transactions` VALUES ("11","TEST-1777659993-1","ws_CO_1777659993_123456","0","The service request is processed successfully.","100","TEST123456","254745959757","0","2026-05-01 21:26:34");
INSERT INTO `transactions` VALUES ("12","TEST-1777663831-1","ws_CO_1777663831_123456","0","","100","TEST123456","254745959757","1","2026-05-01 22:30:31");
INSERT INTO `transactions` VALUES ("13","0a50-4428-b16b-465476ed6a291685071","ws_CO_01052026230528387745959757","1032","","0","","","0","2026-05-01 23:05:38");
INSERT INTO `transactions` VALUES ("14","cee7-4b64-a6de-f4bf1764ccb417241931","ws_CO_01052026230822471745959757","1032","","0","","","0","2026-05-01 23:08:35");
INSERT INTO `transactions` VALUES ("15","8c71-4d1f-8309-ae8c5aaab9a31596181","ws_CO_01052026231052605745959757","1032","","0","","","0","2026-05-01 23:11:14");
INSERT INTO `transactions` VALUES ("16","bd6b-451f-b915-8c19b1d1509821074931","ws_CO_01052026231130877745959757","1032","","0","","","0","2026-05-01 23:11:48");
INSERT INTO `transactions` VALUES ("17","5285-4d3f-a554-5b0d8799646a1742765","ws_CO_01052026231424874745959757","1032","","0","","","0","2026-05-01 23:14:35");
INSERT INTO `transactions` VALUES ("18","f77f-4a79-b6ef-daa9a3920cf91642906","ws_CO_01052026231710292745959757","1032","","0","","","0","2026-05-01 23:17:21");
INSERT INTO `transactions` VALUES ("19","24aa-4bbc-a18a-a61cf906c76a24991932","ws_CO_01052026231755700745959757","1032","","0","","","0","2026-05-01 23:18:04");
INSERT INTO `transactions` VALUES ("20","243c-4ff4-b2ee-b484f2a4631943622137","ws_CO_01052026231845390745959757","1032","","0","","","0","2026-05-01 23:19:03");
INSERT INTO `transactions` VALUES ("21","20c1-4224-8c15-3273867dd8d21756430","ws_CO_01052026232114391745959757","1032","","0","","","0","2026-05-01 23:21:23");
INSERT INTO `transactions` VALUES ("22","8c71-4d1f-8309-ae8c5aaab9a31608902","ws_CO_01052026232302819745959757","1032","","0","","","0","2026-05-01 23:23:11");
INSERT INTO `transactions` VALUES ("23","b1a4-4079-8da7-0fc363f70a63205928","ws_CO_01052026232516429745959757","1032","","0","","","0","2026-05-01 23:25:24");
INSERT INTO `transactions` VALUES ("24","b1a4-4079-8da7-0fc363f70a63273644","ws_CO_02052026005724973745959757","1037","","0","","","0","2026-05-02 00:57:54");
INSERT INTO `transactions` VALUES ("25","8a3d-47ce-b5d3-f697c62c367e1776604","ws_CO_02052026010037008745959757","1032","","0","","","0","2026-05-02 01:00:46");
INSERT INTO `transactions` VALUES ("26","8a3d-47ce-b5d3-f697c62c367e1776807","ws_CO_02052026010100422745959757","1037","","0","","","0","2026-05-02 01:01:29");
INSERT INTO `transactions` VALUES ("27","cb50-4a8c-a8fa-5fcae33e5e6b14195686","ws_CO_02052026010220137745959757","1032","","0","","","0","2026-05-02 01:02:28");
INSERT INTO `transactions` VALUES ("28","cee7-4b64-a6de-f4bf1764ccb417329861","ws_CO_02052026010241324745959757","1032","","0","","","0","2026-05-02 01:02:50");
INSERT INTO `transactions` VALUES ("29","5285-4d3f-a554-5b0d8799646a1824502","ws_CO_02052026010352999745959757","1032","","0","","","0","2026-05-02 01:04:01");
INSERT INTO `transactions` VALUES ("30","faff-4353-91c5-e4b515a7a8aa24816659","ws_CO_02052026010424605745959757","1032","","0","","","0","2026-05-02 01:04:31");
INSERT INTO `transactions` VALUES ("31","734b-4e63-81e2-3144edc318ba1781887","ws_CO_02052026010532043745959757","1032","","0","","","0","2026-05-02 01:05:40");
INSERT INTO `transactions` VALUES ("32","c5f0-4aae-a8b4-53e8526d181a211356","ws_CO_02052026010656527745959757","1032","","0","","","0","2026-05-02 01:07:04");
INSERT INTO `transactions` VALUES ("33","d961-43c1-80e0-c5f9e3ceaff121164333","ws_CO_02052026011050161745959757","1032","","0","","","0","2026-05-02 01:11:08");
INSERT INTO `transactions` VALUES ("34","580d-4a6d-9276-06579ec0b75e1736856","ws_CO_02052026011349344745959757","1032","","0","","","0","2026-05-02 01:13:58");
INSERT INTO `transactions` VALUES ("35","4ddd-4866-824e-3f944c695ba345874857","ws_CO_02052026011420783745959757","1032","","0","","","0","2026-05-02 01:14:33");
INSERT INTO `transactions` VALUES ("36","b18e-4425-8db3-935909f7a54d46259245","ws_CO_02052026011504972745959757","0","","1","UE2N82WFAK","254745959757","1","2026-05-02 01:15:15");
INSERT INTO `transactions` VALUES ("37","b1a4-4079-8da7-0fc363f70a63284358","ws_CO_02052026012035616745959757","1032","","0","","","0","2026-05-02 01:20:48");
INSERT INTO `transactions` VALUES ("38","f43e-4304-89a1-994d43718d5425828419","ws_CO_02052026012155025745959757","1032","","0","","","0","2026-05-02 01:22:04");
INSERT INTO `transactions` VALUES ("39","bd6b-451f-b915-8c19b1d1509821167754","ws_CO_02052026012221792745959757","0","","1","UE2N82WAJL","254745959757","1","2026-05-02 01:22:31");
INSERT INTO `transactions` VALUES ("40","b18e-4425-8db3-935909f7a54d46263775","ws_CO_02052026012537864745959757","0","","1","UE2N82W8ZN","254745959757","1","2026-05-02 01:25:48");
INSERT INTO `transactions` VALUES ("41","3ac6-4536-95db-4482d9bbe1bc24844579","ws_CO_02052026012756440745959757","0","","1","UE2N82WGV1","254745959757","1","2026-05-02 01:28:08");
INSERT INTO `transactions` VALUES ("42","580d-4a6d-9276-06579ec0b75e1752028","ws_CO_02052026015011412745959757","1032","","0","","","0","2026-05-02 01:50:20");
INSERT INTO `transactions` VALUES ("43","5285-4d3f-a554-5b0d8799646a1844274","ws_CO_02052026015039539745959757","0","","1","UE2N82WBRO","254745959757","1","2026-05-02 01:50:52");
INSERT INTO `transactions` VALUES ("44","f12b-4928-9fb7-40b7c9e9571a24895528","ws_CO_02052026015126317745959757","17","","0","","","0","2026-05-02 01:51:38");
INSERT INTO `transactions` VALUES ("45","5285-4d3f-a554-5b0d8799646a1845327","ws_CO_02052026015323984745959757","1032","","0","","","0","2026-05-02 01:53:32");
INSERT INTO `transactions` VALUES ("46","8a3d-47ce-b5d3-f697c62c367e2664612","ws_CO_02052026163548712745959757","1032","","0","","","0","2026-05-02 16:36:00");
INSERT INTO `transactions` VALUES ("47","5285-4d3f-a554-5b0d8799646a2713083","ws_CO_02052026163735983745959757","1032","","0","","","0","2026-05-02 16:37:43");
INSERT INTO `transactions` VALUES ("48","734b-4e63-81e2-3144edc318ba2671506","ws_CO_02052026163812232745959757","1032","","0","","","0","2026-05-02 16:38:21");
INSERT INTO `transactions` VALUES ("49","47bf-4499-a797-c1bfe826e3fe4118907","ws_CO_02052026165253981745959757","1032","","0","","","0","2026-05-02 16:53:06");
INSERT INTO `transactions` VALUES ("50","e356-46bc-981d-c7eb48e9e49c22127815","ws_CO_02052026165517820745959757","1032","","0","","","0","2026-05-02 16:55:24");
INSERT INTO `transactions` VALUES ("51","8c71-4d1f-8309-ae8c5aaab9a32607650","ws_CO_02052026165756210745959757","1032","","0","","","0","2026-05-02 16:58:07");
INSERT INTO `transactions` VALUES ("52","cf39-4b49-9214-58520a73e0552699609","ws_CO_02052026170006595745959757","1032","","0","","","0","2026-05-02 17:00:14");
INSERT INTO `transactions` VALUES ("53","5285-4d3f-a554-5b0d8799646a2752621","ws_CO_02052026170026499745959757","1032","","0","","","0","2026-05-02 17:00:37");
INSERT INTO `transactions` VALUES ("54","cf39-4b49-9214-58520a73e0552703530","ws_CO_02052026170226266745959757","1032","","0","","","0","2026-05-02 17:02:38");
INSERT INTO `transactions` VALUES ("55","734b-4e63-81e2-3144edc318ba2715959","ws_CO_02052026170412442745959757","1032","","0","","","0","2026-05-02 17:04:26");
INSERT INTO `transactions` VALUES ("56","5285-4d3f-a554-5b0d8799646a2759684","ws_CO_02052026170442670745959757","1032","","0","","","0","2026-05-02 17:04:53");
INSERT INTO `transactions` VALUES ("57","6eb1-4876-a7ef-09767c5ebe1a2770855","ws_CO_02052026170524507745959757","0","","1","UE2N82YOV2","254745959757","1","2026-05-02 17:05:36");
INSERT INTO `transactions` VALUES ("58","b424-4bec-973f-fac88b9b7d1925339935","ws_CO_02052026171352918745959757","1032","","0","","","0","2026-05-02 17:14:00");
INSERT INTO `transactions` VALUES ("59","d961-43c1-80e0-c5f9e3ceaff122109287","ws_CO_02052026171604312745959757","1032","","0","","","0","2026-05-02 17:16:12");
INSERT INTO `transactions` VALUES ("60","3ac6-4536-95db-4482d9bbe1bc25784377","ws_CO_02052026171619424745959757","0","","1","UE2N82YQBV","254745959757","1","2026-05-02 17:16:30");
INSERT INTO `transactions` VALUES ("61","47bf-4499-a797-c1bfe826e3fe4171430","ws_CO_02052026172413482745959757","1032","","0","","","0","2026-05-02 17:24:21");
INSERT INTO `transactions` VALUES ("62","243c-4ff4-b2ee-b484f2a4631944661092","ws_CO_02052026172434504745959757","1032","","0","","","0","2026-05-02 17:24:42");
INSERT INTO `transactions` VALUES ("63","243c-4ff4-b2ee-b484f2a4631944661827","ws_CO_02052026172501276745959757","1032","","0","","","0","2026-05-02 17:25:11");
INSERT INTO `transactions` VALUES ("64","b424-4bec-973f-fac88b9b7d1925359827","ws_CO_02052026172606713745959757","1032","","0","","","0","2026-05-02 17:26:17");
INSERT INTO `transactions` VALUES ("65","test-merchant-1777732095","test-checkout-1777732095","0","","100","TEST1777732095","254745959757","1","2026-05-02 17:28:15");
INSERT INTO `transactions` VALUES ("66","test-merchant-1777732113","test-checkout-1777732113","0","","100","TEST1777732113","254745959757","1","2026-05-02 17:28:33");
INSERT INTO `transactions` VALUES ("67","test-merchant-1777732209","test-checkout-1777732209","0","","100","TEST1777732209","254745959757","1","2026-05-02 17:30:09");
INSERT INTO `transactions` VALUES ("68","test-merchant-1777732254","test-checkout-1777732254","0","","100","TEST1777732254","254745959757","1","2026-05-02 17:30:54");
INSERT INTO `transactions` VALUES ("69","","","0","","100","TEST1777732254","254745959757","1","2026-05-02 17:30:54");
INSERT INTO `transactions` VALUES ("70","cb50-4a8c-a8fa-5fcae33e5e6b15169605","ws_CO_02052026173144008745959757","1032","","0","","","0","2026-05-02 17:31:54");
INSERT INTO `transactions` VALUES ("71","e356-46bc-981d-c7eb48e9e49c22190419","ws_CO_02052026173312426745959757","17","","0","","","0","2026-05-02 17:33:22");
INSERT INTO `transactions` VALUES ("72","20c1-4224-8c15-3273867dd8d22826009","ws_CO_02052026174002991745959757","1032","","0","","","0","2026-05-02 17:40:11");
INSERT INTO `transactions` VALUES ("73","cf39-4b49-9214-58520a73e0552767345","ws_CO_02052026174054899745959757","1032","","0","","","0","2026-05-02 17:41:03");
INSERT INTO `transactions` VALUES ("74","c5f0-4aae-a8b4-53e8526d181a1206053","ws_CO_02052026174122058745959757","1032","","0","","","0","2026-05-02 17:41:30");
INSERT INTO `transactions` VALUES ("75","cb50-4a8c-a8fa-5fcae33e5e6b15186297","ws_CO_02052026174137678745959757","0","","1","UE2N82YROX","254745959757","1","2026-05-02 17:41:50");
INSERT INTO `transactions` VALUES ("76","","","0","","1","UE2N82YROX","254745959757","1","2026-05-02 17:41:50");
INSERT INTO `transactions` VALUES ("77","243c-4ff4-b2ee-b484f2a4631944787347","ws_CO_02052026183709911745959757","1032","","0","","","0","2026-05-02 18:37:17");
INSERT INTO `transactions` VALUES ("78","c5f0-4aae-a8b4-53e8526d181a1305265","ws_CO_02052026183740282745959757","1032","","0","","","0","2026-05-02 18:37:51");
INSERT INTO `transactions` VALUES ("79","518d-4d20-a46f-b3b1ed75c58e74067","ws_CO_02052026201847230745959757","1032","","0","","","0","2026-05-02 20:18:57");
INSERT INTO `transactions` VALUES ("80","bd6b-451f-b915-8c19b1d1509822528786","ws_CO_02052026202745294745959757","1032","","0","","","0","2026-05-02 20:27:59");
INSERT INTO `transactions` VALUES ("81","317e-49ad-820f-d975861a3c49179296","ws_CO_02052026202812156745959757","1032","","0","","","0","2026-05-02 20:28:20");
INSERT INTO `transactions` VALUES ("82","003b-44b6-9756-96f607929e89185134","ws_CO_02052026202832686745959757","1032","","0","","","0","2026-05-02 20:28:41");
INSERT INTO `transactions` VALUES ("83","f43e-4304-89a1-994d43718d5427194638","ws_CO_02052026202910438745959757","1032","","0","","","0","2026-05-02 20:29:19");
INSERT INTO `transactions` VALUES ("84","ae66-41da-affc-3da2d24786b7118444","ws_CO_02052026203144708745959757","1032","","0","","","0","2026-05-02 20:31:57");
INSERT INTO `transactions` VALUES ("85","ab98-48b8-b94b-de0f3806796a23030704","ws_CO_02052026224612361745959757","1037","","0","","","0","2026-05-02 22:46:32");
INSERT INTO `transactions` VALUES ("86","24aa-4bbc-a18a-a61cf906c76a26747670","ws_CO_02052026232406429745959757","1032","","0","","","0","2026-05-02 23:24:15");
INSERT INTO `transactions` VALUES ("87","317e-49ad-820f-d975861a3c49430411","ws_CO_02052026232426967745959757","1032","","0","","","0","2026-05-02 23:24:35");
INSERT INTO `transactions` VALUES ("88","cf39-4b49-9214-58520a73e0553355061","ws_CO_02052026232520504745959757","1032","","0","","","0","2026-05-02 23:25:30");
INSERT INTO `transactions` VALUES ("89","f12b-4928-9fb7-40b7c9e9571a26610996","ws_CO_03052026001920438745959757","1032","","0","","","0","2026-05-03 00:19:30");
INSERT INTO `transactions` VALUES ("90","5722-4698-b975-3eba550494af228545","ws_CO_03052026002502060745959757","1032","","0","","","0","2026-05-03 00:25:11");
INSERT INTO `transactions` VALUES ("91","4ddd-4866-824e-3f944c695ba347612792","ws_CO_03052026002609640745959757","1032","","0","","","0","2026-05-03 00:26:18");
INSERT INTO `transactions` VALUES ("92","bac5-4ff5-9cb0-c8aea74f122f578070","ws_CO_03052026004230817745959757","1032","","0","","","0","2026-05-03 00:42:49");
INSERT INTO `transactions` VALUES ("93","280c-4dcb-8547-a619318dd7c3444479","ws_CO_03052026005013294745959757","1032","","0","","","0","2026-05-03 00:50:25");
INSERT INTO `transactions` VALUES ("94","ab98-48b8-b94b-de0f3806796a23161586","ws_CO_03052026005258460745959757","1032","","0","","","0","2026-05-03 00:53:07");
INSERT INTO `transactions` VALUES ("95","ae66-41da-affc-3da2d24786b7499463","ws_CO_03052026010014571745959757","1032","","0","","","0","2026-05-03 01:00:23");
INSERT INTO `transactions` VALUES ("96","cf39-4b49-9214-58520a73e0553446625","ws_CO_03052026010721044745959757","1032","","0","","","0","2026-05-03 01:07:30");
INSERT INTO `transactions` VALUES ("97","317e-4713-9385-903febbe714b673951","ws_CO_03052026010739366745959757","1032","","0","","","0","2026-05-03 01:07:47");
INSERT INTO `transactions` VALUES ("98","5722-4698-b975-3eba550494af260192","ws_CO_03052026010838700745959757","1032","","0","","","0","2026-05-03 01:08:46");
INSERT INTO `transactions` VALUES ("99","faff-4353-91c5-e4b515a7a8aa26587859","ws_CO_03052026010856983745959757","0","","1","UE3N830R5A","254745959757","1","2026-05-03 01:09:09");
INSERT INTO `transactions` VALUES ("100","","","0","","1","UE3N830R5A","254745959757","1","2026-05-03 01:09:09");
INSERT INTO `transactions` VALUES ("101","5722-4698-b975-3eba550494af665116","ws_CO_03052026105704542745959757","1032","","0","","","0","2026-05-03 10:57:11");
INSERT INTO `transactions` VALUES ("102","e0e7-43a0-abb7-83017878444d724680","ws_CO_03052026110027647745959757","1037","","0","","","0","2026-05-03 11:00:56");
INSERT INTO `transactions` VALUES ("103","e575-44f8-9f48-cb08c3280bb1849021","ws_CO_03052026110356112745959757","1032","","0","","","0","2026-05-03 11:04:04");
INSERT INTO `transactions` VALUES ("104","f43e-4304-89a1-994d43718d5428038703","ws_CO_03052026113105485745959757","1032","","0","","","0","2026-05-03 11:31:12");
INSERT INTO `transactions` VALUES ("105","5722-4698-b975-3eba550494af737295","ws_CO_03052026115246528745959757","1032","","0","","","0","2026-05-03 11:52:54");
INSERT INTO `transactions` VALUES ("106","f43e-4304-89a1-994d43718d5428069921","ws_CO_03052026115522944745959757","1032","","0","","","0","2026-05-03 11:55:31");
INSERT INTO `transactions` VALUES ("107","19ef-4913-8503-68662e7d91271004360","ws_CO_03052026115604373745959757","1032","","0","","","0","2026-05-03 11:56:13");
INSERT INTO `transactions` VALUES ("108","518d-4d20-a46f-b3b1ed75c58e995282","ws_CO_03052026115851167745959757","1032","","0","","","0","2026-05-03 11:58:58");
INSERT INTO `transactions` VALUES ("109","21f1-425a-9052-a4369fa992b614827","ws_CO_03052026172355516745959757","1032","","0","","","0","2026-05-03 17:24:02");
INSERT INTO `transactions` VALUES ("110","e575-44f8-9f48-cb08c3280bb11412935","ws_CO_03052026172538792745959757","1032","","0","","","0","2026-05-03 17:25:44");
INSERT INTO `transactions` VALUES ("111","ab98-48b8-b94b-de0f3806796a24107739","ws_CO_03052026172630910745959757","1032","","0","","","0","2026-05-03 17:26:37");
INSERT INTO `transactions` VALUES ("112","b424-4bec-973f-fac88b9b7d1927101687","ws_CO_03052026172703112745959757","1032","","0","","","0","2026-05-03 17:27:09");
INSERT INTO `transactions` VALUES ("113","1985-496c-8299-e2b307ef252b3821793","ws_CO_04052026094615261745959757","1032","","0","","","0","2026-05-04 09:46:27");
INSERT INTO `transactions` VALUES ("114","84cb-4c6e-91a0-4fba293e705363143","ws_CO_06072026180355817745959757","1032","Request Cancelled by user.","0","","","0","2026-07-06 18:04:02");
INSERT INTO `transactions` VALUES ("115","84cb-4c6e-91a0-4fba293e705363336","ws_CO_06072026180700777745959757","1032","Request Cancelled by user.","0","","","0","2026-07-06 18:07:06");
INSERT INTO `transactions` VALUES ("116","5f26-4b5c-be56-a74e7c15db1920995","ws_CO_06072026181305975745959757","1032","Request Cancelled by user.","0","","","0","2026-07-06 18:13:12");
INSERT INTO `transactions` VALUES ("117","934e-485f-93a1-766dc71b6c9a98448","ws_CO_06072026181349475745959757","0","The service request is processed successfully.","1","UG6N8AI79R","254745959757","0","2026-07-06 18:14:11");
INSERT INTO `transactions` VALUES ("118","84cb-4c6e-91a0-4fba293e705364153","ws_CO_06072026181938961745959757","0","The service request is processed successfully.","1","UG6N8AIAGX","254745959757","0","2026-07-06 18:19:54");
INSERT INTO `transactions` VALUES ("119","84cb-4c6e-91a0-4fba293e705364589","ws_CO_06072026182650228745959757","0","The service request is processed successfully.","1","UG6N8AIEFT","254745959757","0","2026-07-06 18:26:59");
INSERT INTO `transactions` VALUES ("120","84cb-4c6e-91a0-4fba293e705365182","ws_CO_06072026183827234745959757","0","The service request is processed successfully.","1","UG6N8AIHB4","254745959757","0","2026-07-06 18:38:37");
INSERT INTO `transactions` VALUES ("121","934e-485f-93a1-766dc71b6c9a98973","ws_CO_06072026183900810745959757","1032","Request Cancelled by user.","0","","","0","2026-07-06 18:39:06");
INSERT INTO `transactions` VALUES ("122","934e-485f-93a1-766dc71b6c9a98986","ws_CO_06072026183934251745959757","0","The service request is processed successfully.","1","UG6N8AIET0","254745959757","0","2026-07-06 18:39:45");
INSERT INTO `transactions` VALUES ("123","d93a-4656-96f7-cb427c2ad97d17082","ws_CO_07072026134424906745959757","1037","No response from user.","0","","","0","2026-07-07 13:44:51");
INSERT INTO `transactions` VALUES ("124","6766-4f14-b588-802e2e59785d5060","ws_CO_07072026134526558745959757","1032","Request Cancelled by user.","0","","","0","2026-07-07 13:45:36");
INSERT INTO `transactions` VALUES ("125","58c3-464f-b59d-de06f0e595a942448","ws_CO_07072026134642480745959757","0","The service request is processed successfully.","1","UG7N8ALA2Y","254745959757","0","2026-07-07 13:46:51");
INSERT INTO `transactions` VALUES ("126","6766-4f14-b588-802e2e59785d26256","ws_CO_07072026234056220745959757","0","The service request is processed successfully.","1","UG7N8ANIWC","254745959757","0","2026-07-07 23:41:15");
INSERT INTO `transactions` VALUES ("127","test-merchant-1783460732","ws_CO_TEST_1783460732","0","The service request is processed successfully.","1","TEST1783460732","254700000000","0","2026-07-08 00:45:32");
INSERT INTO `transactions` VALUES ("128","58c3-464f-b59d-de06f0e595a981710","ws_CO_08072026004628902745959757","0","The service request is processed successfully.","1","UG8N8ANLTG","254745959757","0","2026-07-08 00:46:40");
INSERT INTO `transactions` VALUES ("129","6766-4f14-b588-802e2e59785d53732","ws_CO_08072026113856483745959757","0","The service request is processed successfully.","1","UG8N8AOV3O","254745959757","0","2026-07-08 11:39:10");
INSERT INTO `transactions` VALUES ("130","ff3e-4fa4-abc0-8eb3aa92c0d9111380","ws_CO_15072026011127371745959757","1032","Request Cancelled by user.","0","","","0","2026-07-15 01:11:34");
INSERT INTO `transactions` VALUES ("131","3a62-4214-aa55-1c05a6d85a1c29560","ws_CO_15072026011155751745959757","0","The service request is processed successfully.","1","UGFN8BG3G5","254745959757","0","2026-07-15 01:12:04");
INSERT INTO `transactions` VALUES ("132","3562-4784-aa83-0330f61ef368132943","ws_CO_16072026121807872745959757","0","The service request is processed successfully.","1","UGGN8BL5Y8","254745959757","0","2026-07-16 12:18:16");
INSERT INTO `transactions` VALUES ("133","3562-4784-aa83-0330f61ef368132985","ws_CO_16072026121906511745959757","0","The service request is processed successfully.","2","UGGN8BL4JP","254745959757","0","2026-07-16 12:19:13");
INSERT INTO `transactions` VALUES ("134","6839-428d-8589-2bcb00296f3742632","ws_CO_19072026161247034745959757","1032","Request Cancelled by user.","0","","","0","2026-07-19 16:13:03");
INSERT INTO `transactions` VALUES ("135","7cd0-4a28-8d04-cb27fe4732c642249","ws_CO_19072026162300421745959757","0","The service request is processed successfully.","1","UGJN801DNO","254745959757","0","2026-07-19 16:23:09");
INSERT INTO `transactions` VALUES ("136","6839-428d-8589-2bcb00296f3743210","ws_CO_19072026162337917745959757","0","The service request is processed successfully.","2","UGJN801C80","254745959757","0","2026-07-19 16:23:46");
INSERT INTO `transactions` VALUES ("137","6839-428d-8589-2bcb00296f3756241","ws_CO_19072026200320607745959757","0","The service request is processed successfully.","2","UGJN802DVF","254745959757","0","2026-07-19 20:03:38");
INSERT INTO `transactions` VALUES ("138","test123","test456","0","Test callback","0","","","0","2026-07-19 22:33:07");
INSERT INTO `transactions` VALUES ("139","6839-428d-8589-2bcb00296f3768705","ws_CO_19072026231905970745959757","0","The service request is processed successfully.","1","UGJN8032CK","254745959757","0","2026-07-19 23:19:18");
INSERT INTO `transactions` VALUES ("140","6839-428d-8589-2bcb00296f3788764","ws_CO_20072026051830140745959757","1032","Request Cancelled by user.","0","","","0","2026-07-20 05:18:37");
INSERT INTO `transactions` VALUES ("141","b824-4eab-b51c-485633e35ec2125354","ws_CO_22072026191848584745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 19:18:55");
INSERT INTO `transactions` VALUES ("142","b824-4eab-b51c-485633e35ec2125392","ws_CO_22072026191945220745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 19:20:04");
INSERT INTO `transactions` VALUES ("143","b824-4eab-b51c-485633e35ec2125492","ws_CO_22072026192258490745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 19:23:04");
INSERT INTO `transactions` VALUES ("144","f158-452a-801f-e6b00aad1141126480","ws_CO_22072026192907207745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 19:29:12");
INSERT INTO `transactions` VALUES ("145","b824-4eab-b51c-485633e35ec2125844","ws_CO_22072026192952656745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 19:30:12");
INSERT INTO `transactions` VALUES ("146","f158-452a-801f-e6b00aad1141126676","ws_CO_22072026193128295745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 19:31:32");
INSERT INTO `transactions` VALUES ("147","b824-4eab-b51c-485633e35ec2126014","ws_CO_22072026193405309745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 19:34:29");
INSERT INTO `transactions` VALUES ("148","f158-452a-801f-e6b00aad1141127195","ws_CO_22072026193812993745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 19:38:18");
INSERT INTO `transactions` VALUES ("149","b824-4eab-b51c-485633e35ec2126266","ws_CO_22072026194007769745959757","0","The service request is processed successfully.","1","UGMN80E49M","254745959757","0","2026-07-22 19:40:16");
INSERT INTO `transactions` VALUES ("150","b824-4eab-b51c-485633e35ec2126329","ws_CO_22072026194127356745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 19:41:32");
INSERT INTO `transactions` VALUES ("151","f158-452a-801f-e6b00aad1141127378","ws_CO_22072026194156136745959757","0","The service request is processed successfully.","1","UGMN80DZXL","254745959757","0","2026-07-22 19:42:04");
INSERT INTO `transactions` VALUES ("152","b824-4eab-b51c-485633e35ec2126463","ws_CO_22072026194449556745959757","2001","The initiator information is invalid.","0","","","0","2026-07-22 19:44:56");
INSERT INTO `transactions` VALUES ("153","b824-4eab-b51c-485633e35ec2126788","ws_CO_22072026195116832745959757","0","The service request is processed successfully.","1","UGMN80E4ND","254745959757","0","2026-07-22 19:51:25");
INSERT INTO `transactions` VALUES ("154","f158-452a-801f-e6b00aad1141128384","ws_CO_22072026195544088745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 19:55:49");
INSERT INTO `transactions` VALUES ("155","b824-4eab-b51c-485633e35ec2127035","ws_CO_22072026195949754745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 19:59:56");
INSERT INTO `transactions` VALUES ("156","f158-452a-801f-e6b00aad1141139419","ws_CO_22072026215340711745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 21:53:45");
INSERT INTO `transactions` VALUES ("157","b824-4eab-b51c-485633e35ec2132757","ws_CO_22072026220341003745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 22:03:49");
INSERT INTO `transactions` VALUES ("158","f158-452a-801f-e6b00aad1141140683","ws_CO_22072026220550240745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 22:05:55");
INSERT INTO `transactions` VALUES ("159","f158-452a-801f-e6b00aad1141141488","ws_CO_22072026221338537745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 22:13:43");
INSERT INTO `transactions` VALUES ("160","f158-452a-801f-e6b00aad1141141558","ws_CO_22072026221423324745959757","0","The service request is processed successfully.","1","UGMN80ELXU","254745959757","0","2026-07-22 22:14:35");
INSERT INTO `transactions` VALUES ("161","b824-4eab-b51c-485633e35ec2133488","ws_CO_22072026221634984745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 22:16:39");
INSERT INTO `transactions` VALUES ("162","f158-452a-801f-e6b00aad1141142118","ws_CO_22072026222027706745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 22:20:45");
INSERT INTO `transactions` VALUES ("163","b824-4eab-b51c-485633e35ec2133791","ws_CO_22072026222253945745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 22:23:02");
INSERT INTO `transactions` VALUES ("164","b824-4eab-b51c-485633e35ec2133849","ws_CO_22072026222353546745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 22:24:02");
INSERT INTO `transactions` VALUES ("165","f158-452a-801f-e6b00aad1141142608","ws_CO_22072026222529556745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 22:25:37");
INSERT INTO `transactions` VALUES ("166","b824-4eab-b51c-485633e35ec2134011","ws_CO_22072026222640997745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 22:26:51");
INSERT INTO `transactions` VALUES ("167","f158-452a-801f-e6b00aad1141143200","ws_CO_22072026222944291745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 22:29:49");
INSERT INTO `transactions` VALUES ("168","f158-452a-801f-e6b00aad1141143287","ws_CO_22072026223016661745959757","0","The service request is processed successfully.","1","UGMN80ES0M","254745959757","0","2026-07-22 22:30:24");
INSERT INTO `transactions` VALUES ("169","b824-4eab-b51c-485633e35ec2134478","ws_CO_22072026223423293745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 22:34:30");
INSERT INTO `transactions` VALUES ("170","f158-452a-801f-e6b00aad1141143843","ws_CO_22072026223457757745959757","0","The service request is processed successfully.","1","UGMN80ES2Y","254745959757","0","2026-07-22 22:35:07");
INSERT INTO `transactions` VALUES ("171","f158-452a-801f-e6b00aad1141144177","ws_CO_22072026223739757745959757","1037","No response from user.","0","","","0","2026-07-22 22:38:06");
INSERT INTO `transactions` VALUES ("172","b824-4eab-b51c-485633e35ec2134749","ws_CO_22072026223818462745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 22:38:28");
INSERT INTO `transactions` VALUES ("173","b824-4eab-b51c-485633e35ec2135680","ws_CO_22072026225413524745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 22:54:18");
INSERT INTO `transactions` VALUES ("174","f158-452a-801f-e6b00aad1141146234","ws_CO_22072026225433145745959757","0","The service request is processed successfully.","1","UGMN80EP8R","254745959757","0","2026-07-22 22:54:42");
INSERT INTO `transactions` VALUES ("175","f158-452a-801f-e6b00aad1141147184","ws_CO_22072026230315885745959757","1032","Request Cancelled by user.","0","","","0","2026-07-22 23:03:25");
INSERT INTO `transactions` VALUES ("176","f158-452a-801f-e6b00aad1141153694","ws_CO_23072026001309411745959757","1032","Request Cancelled by user.","0","","","0","2026-07-23 00:13:17");
INSERT INTO `transactions` VALUES ("177","f158-452a-801f-e6b00aad1141251317","ws_CO_23072026192425141745959757","1032","Request Cancelled by user.","0","","","0","2026-07-23 19:24:32");
INSERT INTO `transactions` VALUES ("178","f158-452a-801f-e6b00aad1141251324","ws_CO_23072026192449642745959757","1032","Request Cancelled by user.","0","","","0","2026-07-23 19:24:56");
INSERT INTO `transactions` VALUES ("179","b824-4eab-b51c-485633e35ec2201693","ws_CO_23072026192534871745959757","0","The service request is processed successfully.","1","UGNN80HUQC","254745959757","0","2026-07-23 19:25:44");
INSERT INTO `transactions` VALUES ("180","081a-46f3-bf79-f6857fb872e710000","ws_CO_24072026013646597745959757","1032","Request Cancelled by user.","0","","","0","2026-07-24 01:36:53");
INSERT INTO `transactions` VALUES ("181","6e47-4967-bcb9-74445bede7d227056","ws_CO_24072026013708206745959757","1032","Request Cancelled by user.","0","","","0","2026-07-24 01:37:13");
INSERT INTO `transactions` VALUES ("182","6e47-4967-bcb9-74445bede7d227072","ws_CO_24072026013732159745959757","0","The service request is processed successfully.","1","UGON80IRKH","254745959757","0","2026-07-24 01:37:42");
INSERT INTO `transactions` VALUES ("183","081a-46f3-bf79-f6857fb872e710101","ws_CO_24072026014008491745959757","0","The service request is processed successfully.","1","UGON80IRKO","254745959757","0","2026-07-24 01:40:18");
INSERT INTO `transactions` VALUES ("184","17b4-4bbb-a2b0-7a04972eda64210","ws_CO_24072026162432414745959757","1037","No response from user.","0","","","0","2026-07-24 16:24:59");
INSERT INTO `transactions` VALUES ("185","6e47-4967-bcb9-74445bede7d2104612","ws_CO_24072026162513694745959757","1032","Request Cancelled by user.","0","","","0","2026-07-24 16:25:16");
INSERT INTO `transactions` VALUES ("186","081a-46f3-bf79-f6857fb872e753393","ws_CO_24072026162537306745959757","1037","No response from user.","0","","","0","2026-07-24 16:26:04");
INSERT INTO `transactions` VALUES ("187","081a-46f3-bf79-f6857fb872e753435","ws_CO_24072026162654570745959757","1037","No response from user.","0","","","0","2026-07-24 16:27:22");
INSERT INTO `transactions` VALUES ("188","081a-46f3-bf79-f6857fb872e753462","ws_CO_24072026162751942745959757","0","The service request is processed successfully.","1","UGON80L1VF","254745959757","0","2026-07-24 16:28:04");
INSERT INTO `transactions` VALUES ("189","b13d-4e1d-8fb5-0f0d66c4323014612","ws_CO_25072026024826291745959757","1032","Request Cancelled by user.","0","","","0","2026-07-25 02:48:34");
INSERT INTO `transactions` VALUES ("190","6e47-4967-bcb9-74445bede7d2149207","ws_CO_25072026024934806745959757","1032","Request Cancelled by user.","0","","","0","2026-07-25 02:49:41");
INSERT INTO `transactions` VALUES ("191","b13d-4e1d-8fb5-0f0d66c4323014685","ws_CO_25072026025002278745959757","2001","The initiator information is invalid.","0","","","0","2026-07-25 02:50:11");
INSERT INTO `transactions` VALUES ("192","b13d-4e1d-8fb5-0f0d66c4323014736","ws_CO_25072026025028152745959757","0","The service request is processed successfully.","1","UGPN80MX5K","254745959757","0","2026-07-25 02:50:40");
INSERT INTO `transactions` VALUES ("193","6e47-4967-bcb9-74445bede7d2149995","ws_CO_25072026030404591745959757","1032","Request Cancelled by user.","0","","","0","2026-07-25 03:04:10");
INSERT INTO `transactions` VALUES ("194","b13d-4e1d-8fb5-0f0d66c4323016711","ws_CO_25072026033934439745959757","1032","Request Cancelled by user.","0","","","0","2026-07-25 03:39:40");
INSERT INTO `transactions` VALUES ("195","b13d-4e1d-8fb5-0f0d66c4323016767","ws_CO_25072026034015366745959757","1032","Request Cancelled by user.","0","","","0","2026-07-25 03:40:21");
INSERT INTO `transactions` VALUES ("196","77aa-47e7-8a39-5426eb3fbe8142006","ws_CO_27072026224640357745959757","1032","Request Cancelled by user.","0","","","0","2026-07-27 22:46:46");
INSERT INTO `transactions` VALUES ("197","8544-4e2a-bd68-bbd981b518db53277","ws_CO_27072026230608422745959757","1032","Request Cancelled by user.","0","","","0","2026-07-27 23:06:15");
INSERT INTO `transactions` VALUES ("198","77aa-47e7-8a39-5426eb3fbe8143189","ws_CO_27072026231301446745959757","1032","Request Cancelled by user.","0","","","0","2026-07-27 23:13:08");
INSERT INTO `transactions` VALUES ("199","77aa-47e7-8a39-5426eb3fbe8143497","ws_CO_27072026231926281745959757","1032","Request Cancelled by user.","0","","","0","2026-07-27 23:19:34");
INSERT INTO `transactions` VALUES ("200","77aa-47e7-8a39-5426eb3fbe8143513","ws_CO_27072026231959145745959757","1032","Request Cancelled by user.","0","","","0","2026-07-27 23:20:06");
INSERT INTO `transactions` VALUES ("201","77aa-47e7-8a39-5426eb3fbe8145057","ws_CO_27072026235201242745959757","1032","Request Cancelled by user.","0","","","0","2026-07-27 23:52:07");
INSERT INTO `transactions` VALUES ("202","8544-4e2a-bd68-bbd981b518db55454","ws_CO_27072026235451767745959757","1032","Request Cancelled by user.","0","","","0","2026-07-27 23:54:56");
INSERT INTO `transactions` VALUES ("203","8544-4e2a-bd68-bbd981b518db55483","ws_CO_27072026235506174745959757","2001","The initiator information is invalid.","0","","","0","2026-07-27 23:55:14");
INSERT INTO `transactions` VALUES ("204","8544-4e2a-bd68-bbd981b518db55511","ws_CO_27072026235559235745959757","2001","The initiator information is invalid.","0","","","0","2026-07-27 23:56:07");
INSERT INTO `transactions` VALUES ("205","8544-4e2a-bd68-bbd981b518db55538","ws_CO_27072026235626477745959757","0","The service request is processed successfully.","1","UGRN80YGKH","254745959757","0","2026-07-27 23:56:39");
INSERT INTO `transactions` VALUES ("206","8544-4e2a-bd68-bbd981b518db55634","ws_CO_27072026235910034745959757","0","The service request is processed successfully.","1","UGRN80YL3B","254745959757","0","2026-07-27 23:59:21");
INSERT INTO `transactions` VALUES ("207","77aa-47e7-8a39-5426eb3fbe8145559","ws_CO_28072026000400307745959757","0","The service request is processed successfully.","1","UGSN80YGLV","254745959757","0","2026-07-28 00:04:11");
INSERT INTO `transactions` VALUES ("208","77aa-47e7-8a39-5426eb3fbe8145635","ws_CO_28072026000533002745959757","1032","Request Cancelled by user.","0","","","0","2026-07-28 00:05:37");
INSERT INTO `transactions` VALUES ("209","8544-4e2a-bd68-bbd981b518db55927","ws_CO_28072026000554330745959757","0","The service request is processed successfully.","1","UGSN80YOJL","254745959757","0","2026-07-28 00:06:05");
INSERT INTO `transactions` VALUES ("210","8544-4e2a-bd68-bbd981b518db56027","ws_CO_28072026000836774745959757","0","The service request is processed successfully.","1","UGSN80YNXT","254745959757","0","2026-07-28 00:08:47");
INSERT INTO `transactions` VALUES ("211","77aa-47e7-8a39-5426eb3fbe8145898","ws_CO_28072026001244768745959757","0","The service request is processed successfully.","1","UGSN80YL5F","254745959757","0","2026-07-28 00:12:57");
INSERT INTO `transactions` VALUES ("212","8544-4e2a-bd68-bbd981b518db65162","ws_CO_280720260356367745959757","1032","Request Cancelled by user.","0","","","0","2026-07-28 03:56:43");
INSERT INTO `transactions` VALUES ("213","77aa-47e7-8a39-5426eb3fbe8155117","ws_CO_280720260357126745959757","0","The service request is processed successfully.","1","UGSN80YMV9","254745959757","0","2026-07-28 03:57:37");
INSERT INTO `transactions` VALUES ("214","a399-4d4a-b8cf-cb102e9a964a30301","ws_CO_280720261446500745959757","1032","Request Cancelled by user.","0","","","0","2026-07-28 14:47:05");
INSERT INTO `transactions` VALUES ("215","125e-4091-abd7-137c1517ac6f110190","ws_CO_310720261838012745959757","1032","Request Cancelled by user.","0","","","0","2026-07-31 18:38:07");
INSERT INTO `transactions` VALUES ("216","125e-4091-abd7-137c1517ac6f110209","ws_CO_310720261838173745959757","0","The service request is processed successfully.","10","UGVN81DSUB","254745959757","0","2026-07-31 18:38:24");
INSERT INTO `transactions` VALUES ("217","4eb4-451f-ae34-bbe27460f2b456759","ws_CO_010820261832572745959757","0","The service request is processed successfully.","10","UH1N81I7DG","254745959757","0","2026-08-01 18:33:06");
INSERT INTO `transactions` VALUES ("218","4eb4-451f-ae34-bbe27460f2b485647","ws_CO_020820260453351745959757","1032","Request Cancelled by user.","0","","","0","2026-08-02 04:53:44");
INSERT INTO `transactions` VALUES ("219","4eb4-451f-ae34-bbe27460f2b485778","ws_CO_020820260456369745959757","1032","Request Cancelled by user.","0","","","0","2026-08-02 04:56:51");
INSERT INTO `transactions` VALUES ("220","9f5f-48fc-bbdc-e469ab55200c24517","ws_CO_020820260459253745959757","1032","Request Cancelled by user.","0","","","0","2026-08-02 04:59:35");
INSERT INTO `transactions` VALUES ("221","9f5f-48fc-bbdc-e469ab55200c24630","ws_CO_020820260501328745959757","1032","Request Cancelled by user.","0","","","0","2026-08-02 05:01:45");
INSERT INTO `transactions` VALUES ("222","4eb4-451f-ae34-bbe27460f2b486023","ws_CO_020820260503071745959757","1032","Request Cancelled by user.","0","","","0","2026-08-02 05:03:16");
INSERT INTO `transactions` VALUES ("223","4eb4-451f-ae34-bbe27460f2b486132","ws_CO_020820260505590745959757","1032","Request Cancelled by user.","0","","","0","2026-08-02 05:06:06");
INSERT INTO `transactions` VALUES ("224","9f5f-48fc-bbdc-e469ab55200c24822","ws_CO_020820260507197745959757","1032","Request Cancelled by user.","0","","","0","2026-08-02 05:07:28");
INSERT INTO `transactions` VALUES ("225","4eb4-451f-ae34-bbe27460f2b486448","ws_CO_020820260512433745959757","1032","Request Cancelled by user.","0","","","0","2026-08-02 05:12:52");
INSERT INTO `transactions` VALUES ("226","4eb4-451f-ae34-bbe27460f2b486532","ws_CO_020820260514557745959757","1032","Request Cancelled by user.","0","","","0","2026-08-02 05:15:06");
INSERT INTO `transactions` VALUES ("227","4eb4-451f-ae34-bbe27460f2b486562","ws_CO_020820260515375745959757","1032","Request Cancelled by user.","0","","","0","2026-08-02 05:15:44");
INSERT INTO `transactions` VALUES ("228","4eb4-451f-ae34-bbe27460f2b486611","ws_CO_020820260517256745959757","0","The service request is processed successfully.","1","UH2N81JTH9","254745959757","0","2026-08-02 05:17:42");
INSERT INTO `transactions` VALUES ("229","4eb4-451f-ae34-bbe27460f2b4100187","ws_CO_020820261057269745959757","1032","Request Cancelled by user.","0","","","0","2026-08-02 10:57:35");
INSERT INTO `transactions` VALUES ("230","4eb4-451f-ae34-bbe27460f2b4100209","ws_CO_020820261058005745959757","0","The service request is processed successfully.","1","UH2N81KD8M","254745959757","0","2026-08-02 10:58:08");
INSERT INTO `transactions` VALUES ("231","4eb4-451f-ae34-bbe27460f2b4100363","ws_CO_020820261101050745959757","0","The service request is processed successfully.","1","UH2N81KK9Y","254745959757","0","2026-08-02 11:01:14");
INSERT INTO `transactions` VALUES ("232","4eb4-451f-ae34-bbe27460f2b4101453","ws_CO_020820261125506745959757","0","The service request is processed successfully.","1","UH2N81KKQF","254745959757","0","2026-08-02 11:26:02");
INSERT INTO `transactions` VALUES ("233","4eb4-451f-ae34-bbe27460f2b4101543","ws_CO_020820261127353745959757","1032","Request Cancelled by user.","0","","","0","2026-08-02 11:27:45");
INSERT INTO `transactions` VALUES ("234","4eb4-451f-ae34-bbe27460f2b4101895","ws_CO_020820261136090745959757","1032","Request Cancelled by user.","0","","","0","2026-08-02 11:36:18");
INSERT INTO `transactions` VALUES ("235","4eb4-451f-ae34-bbe27460f2b4102432","ws_CO_020820261146411745959757","1032","Request Cancelled by user.","0","","","0","2026-08-02 11:46:53");
INSERT INTO `transactions` VALUES ("236","4eb4-451f-ae34-bbe27460f2b4102549","ws_CO_020820261149554745959757","0","The service request is processed successfully.","1","UH2N81KM5D","254745959757","0","2026-08-02 11:50:15");
INSERT INTO `transactions` VALUES ("237","9f5f-48fc-bbdc-e469ab55200c39808","ws_CO_020820261206203745959757","1032","Request Cancelled by user.","0","","","0","2026-08-02 12:06:31");
INSERT INTO `transactions` VALUES ("238","4eb4-451f-ae34-bbe27460f2b4103327","ws_CO_020820261208396745959757","1032","Request Cancelled by user.","0","","","0","2026-08-02 12:08:46");
INSERT INTO `transactions` VALUES ("239","4eb4-451f-ae34-bbe27460f2b4103354","ws_CO_020820261209034745959757","0","The service request is processed successfully.","1","UH2N81KMHH","254745959757","0","2026-08-02 12:09:14");
INSERT INTO `transactions` VALUES ("240","9f5f-48fc-bbdc-e469ab55200c60453","ws_CO_020820261807145745959757","0","The service request is processed successfully.","1","UH2N81M9AK","254745959757","0","2026-08-02 18:07:27");
INSERT INTO `transactions` VALUES ("241","4eb4-451f-ae34-bbe27460f2b4128030","ws_CO_020820261810480745959757","0","The service request is processed successfully.","1","UH2N81M9DY","254745959757","0","2026-08-02 18:10:58");
INSERT INTO `transactions` VALUES ("242","4eb4-451f-ae34-bbe27460f2b4128151","ws_CO_020820261812020745959757","0","The service request is processed successfully.","1","UH2N81M5EY","254745959757","0","2026-08-02 18:12:10");
INSERT INTO `transactions` VALUES ("243","9f5f-48fc-bbdc-e469ab55200c61089","ws_CO_020820261818320745959757","0","The service request is processed successfully.","1","UH2N81MB50","254745959757","0","2026-08-02 18:18:41");
INSERT INTO `transactions` VALUES ("244","9f5f-48fc-bbdc-e469ab55200c62011","ws_CO_020820261833129745959757","0","The service request is processed successfully.","1","UH2N81MCZ1","254745959757","0","2026-08-02 18:33:21");
INSERT INTO `transactions` VALUES ("245","TEST-1785685423","ws_CO_TEST_1785685423","0","The service request is processed successfully.","100","TEST123456","254712345678","0","2026-08-02 18:43:44");
INSERT INTO `transactions` VALUES ("246","TEST-1785685457","ws_CO_020820261838444745959757","0","The service request is processed successfully.","50","TEST123456","254712345678","0","2026-08-02 18:44:17");
INSERT INTO `transactions` VALUES ("247","9f5f-48fc-bbdc-e469ab55200c62633","ws_CO_020820261844571745959757","0","The service request is processed successfully.","1","UH2N81MACX","254745959757","0","2026-08-02 18:45:06");
INSERT INTO `transactions` VALUES ("248","4eb4-451f-ae34-bbe27460f2b4130267","ws_CO_020820261848324745959757","0","The service request is processed successfully.","1","UH2N81MEDX","254745959757","0","2026-08-02 18:48:41");
INSERT INTO `transactions` VALUES ("249","4eb4-451f-ae34-bbe27460f2b4141352","ws_CO_020820262248314745959757","0","The service request is processed successfully.","1","UH2N81NJPH","254745959757","0","2026-08-02 22:48:44");


-- Table structure for `user_settings`
DROP TABLE IF EXISTS `user_settings`;
CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email_uploads` tinyint(1) DEFAULT 1,
  `email_comments` tinyint(1) DEFAULT 1,
  `email_updates` tinyint(1) DEFAULT 0,
  `show_profile` tinyint(1) DEFAULT 1,
  `show_email` tinyint(1) DEFAULT 0,
  `theme` varchar(20) DEFAULT 'light',
  `language` varchar(10) DEFAULT 'en',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `user_settings` VALUES ("1","1","1","1","0","1","0","dark","en","2026-07-30 15:31:53","2026-07-31 00:25:10");
INSERT INTO `user_settings` VALUES ("3","30","1","1","1","1","1","light","en","2026-07-31 21:25:18","2026-08-01 13:22:49");


-- Table structure for `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  `email_verified` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `users` VALUES ("1","Brian Onyango","otienobrian029@gmail.com","$2y$10$mbbgZh98BWWdD0JAGo98a.cRm7s/szNj2kBid3ceJc8Mq2Ms6a.em","admin","","2025-07-12 13:10:16","1","086722","2026-07-31 09:08:33","","","0","2026-07-31 15:27:02","");
INSERT INTO `users` VALUES ("30","Brian Onyango","otisbrian46@gmail.com","$2y$10$iHhlx4AMUtvHb.tsieA9oO9fY5.2ahVKmuLzVqDZUFgyW.dv4Qps6","","","","1","","","","","0","2026-07-31 15:27:02","2026-08-01 13:21:14");
INSERT INTO `users` VALUES ("3","omar","omarwaraka10@gmail.com","$2y$10$gbcQq8JsJc.Fw1x3HyPn4einwXC.NV063kKBKK4Ttsq93tK5G1EWO","user","","","1","","","","","0","2026-07-31 15:27:02","");
INSERT INTO `users` VALUES ("5","stanley juma","stareen258@gmail.com","$2y$10$8/7D1lCSHZF463hPmiouo.uN5q40NVg.4mb6sCnT343akQvaHeT7m","user","","","1","","","","","0","2026-07-31 15:27:02","");
INSERT INTO `users` VALUES ("6","William Steve Odhiambo","williamsteve10699@gmail.com","$2y$10$9ILMPtccH2Om9d7sVFacGedBVGTVUg.wKL/xChXBP9GgmQmH58vH6","user","","","1","","","","","0","2026-07-31 15:27:02","");
INSERT INTO `users` VALUES ("7","Anjeline Auma","anjelineauma@gmail.com","$2y$10$sBCBTsDBFIkdFrLjGw3rNeOTegcKI4KMzSWYbHAUwHplS1zJLRN7e","admin","","","1","","","","","0","2026-07-31 15:27:02","");
INSERT INTO `users` VALUES ("10","Jiven ochieng","Onsongojiven095@gmail.com","$2y$10$ibVeVvzzkBfWcLsd2Pwvaef7kXBXEPSXZyuUf3ZRKRzvFXi4M4DyG","user","","","1","","","","","0","2026-07-31 15:27:02","");
INSERT INTO `users` VALUES ("11","Brian Philip Ombiro ","ombirophilibra@gmail.com","$2y$10$hWpvE297aEETC/YY82jkPexYDyNdLTGXRopN2Jl/4n6ex4/vtOPEO","user","","","1","","","","","0","2026-07-31 15:27:02","");
INSERT INTO `users` VALUES ("15","omundo peter","omundopeter@6gmail.com","$2y$10$1VAAWJTciZrryznlZXQKnuLE5l931VYSfeWkzhAOT4BlSjAk55kIy","user","","","1","","","","","0","2026-07-31 15:27:02","");
INSERT INTO `users` VALUES ("27","Kenyaeduhub","kenyaeduhub@gmail.com","$2y$10$XEpuf9IoF2xJA8MTeYMbV.vpL9AgO46k9Dxs793TbB6qHEXGYN5m6","admin","","","1","","","","","0","2026-07-31 15:27:02","");
INSERT INTO `users` VALUES ("28","Omollo Vincent","vincentomollo22@gmail.com","$2y$10$2qIDpJ5jyrJfngZoG0PGpO/DnbvivzQU5F4Rp3gSAXL7qVSn2hrYS","user","","","1","","","","","0","2026-07-31 15:27:02","");


