-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 21, 2025 at 06:37 PM
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
-- Database: `student_results_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL,
  `subject_id` int(11) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late') DEFAULT 'present',
  `teacher_id` int(11) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `subject_id`, `date`, `status`, `teacher_id`, `created_at`, `updated_at`) VALUES
(1, 11, 4, '2025-10-16', 'present', 4, '2025-10-16 17:58:15', '2025-10-16 17:58:15'),
(2, 9, 4, '2025-10-16', 'present', 4, '2025-10-16 17:58:15', '2025-10-16 17:58:15'),
(3, 10, 4, '2025-10-16', 'late', 4, '2025-10-16 17:58:15', '2025-10-16 17:58:15'),
(4, 12, 4, '2025-10-16', 'present', 4, '2025-10-16 17:58:15', '2025-10-16 17:58:15'),
(5, 11, 4, '2025-10-16', 'present', 4, '2025-10-16 17:58:30', '2025-10-16 17:58:30'),
(6, 9, 4, '2025-10-16', 'present', 4, '2025-10-16 17:58:30', '2025-10-16 17:58:30'),
(7, 10, 4, '2025-10-16', 'late', 4, '2025-10-16 17:58:30', '2025-10-16 17:58:30'),
(8, 12, 4, '2025-10-16', 'present', 4, '2025-10-16 17:58:30', '2025-10-16 17:58:30'),
(9, 11, 4, '2025-10-16', 'present', 4, '2025-10-16 18:13:20', '2025-10-16 18:13:20'),
(10, 9, 4, '2025-10-16', 'present', 4, '2025-10-16 18:13:20', '2025-10-16 18:13:20'),
(11, 10, 4, '2025-10-16', 'late', 4, '2025-10-16 18:13:20', '2025-10-16 18:13:20'),
(12, 12, 4, '2025-10-16', 'present', 4, '2025-10-16 18:13:20', '2025-10-16 18:13:20'),
(13, 11, 4, '2025-10-24', 'present', 5, '2025-10-24 09:11:42', '2025-10-24 09:11:42'),
(14, 9, 4, '2025-10-24', 'absent', 5, '2025-10-24 09:11:42', '2025-10-24 09:11:42'),
(15, 10, 4, '2025-10-24', 'absent', 5, '2025-10-24 09:11:42', '2025-10-24 09:11:42'),
(16, 12, 4, '2025-10-24', 'late', 5, '2025-10-24 09:11:42', '2025-10-24 09:11:42'),
(17, 11, 4, '2025-10-24', 'present', 4, '2025-10-24 09:13:00', '2025-10-24 09:13:00'),
(18, 9, 4, '2025-10-24', 'absent', 4, '2025-10-24 09:13:00', '2025-10-24 09:13:00'),
(19, 10, 4, '2025-10-24', 'late', 4, '2025-10-24 09:13:00', '2025-10-24 09:13:00'),
(20, 12, 4, '2025-10-24', 'present', 4, '2025-10-24 09:13:00', '2025-10-24 09:13:00'),
(21, 11, 4, '2025-10-24', 'present', 4, '2025-10-24 09:13:41', '2025-10-24 09:13:41'),
(22, 9, 4, '2025-10-24', 'absent', 4, '2025-10-24 09:13:41', '2025-10-24 09:13:41'),
(23, 10, 4, '2025-10-24', 'late', 4, '2025-10-24 09:13:41', '2025-10-24 09:13:41'),
(24, 12, 4, '2025-10-24', 'present', 4, '2025-10-24 09:13:41', '2025-10-24 09:13:41'),
(25, 11, 3, '2025-11-08', 'present', 4, '2025-11-08 22:53:19', '2025-11-08 22:53:19'),
(26, 9, 3, '2025-11-08', 'present', 4, '2025-11-08 22:53:19', '2025-11-08 22:53:19'),
(27, 10, 3, '2025-11-08', 'present', 4, '2025-11-08 22:53:19', '2025-11-08 22:53:19'),
(28, 12, 3, '2025-11-08', 'present', 4, '2025-11-08 22:53:19', '2025-11-08 22:53:19'),
(29, 11, 3, '2025-11-08', 'present', 4, '2025-11-08 22:55:06', '2025-11-08 22:55:06'),
(30, 9, 3, '2025-11-08', 'present', 4, '2025-11-08 22:55:06', '2025-11-08 22:55:06'),
(31, 10, 3, '2025-11-08', 'present', 4, '2025-11-08 22:55:06', '2025-11-08 22:55:06'),
(32, 12, 3, '2025-11-08', 'present', 4, '2025-11-08 22:55:06', '2025-11-08 22:55:06'),
(33, 11, 4, '2025-11-13', 'present', 5, '2025-11-13 07:30:49', '2025-11-13 07:30:49'),
(34, 9, 4, '2025-11-13', 'absent', 5, '2025-11-13 07:30:49', '2025-11-13 07:30:49'),
(35, 10, 4, '2025-11-13', 'present', 5, '2025-11-13 07:30:49', '2025-11-13 07:30:49'),
(36, 12, 4, '2025-11-13', 'absent', 5, '2025-11-13 07:30:49', '2025-11-13 07:30:49'),
(37, 11, 4, '2025-11-13', 'absent', 5, '2025-11-13 07:31:30', '2025-11-13 07:31:30'),
(38, 9, 4, '2025-11-13', 'absent', 5, '2025-11-13 07:31:30', '2025-11-13 07:31:30'),
(39, 10, 4, '2025-11-13', 'absent', 5, '2025-11-13 07:31:30', '2025-11-13 07:31:30'),
(40, 12, 4, '2025-11-13', 'absent', 5, '2025-11-13 07:31:30', '2025-11-13 07:31:30'),
(41, 11, 4, '2025-11-13', 'present', 5, '2025-11-13 07:32:25', '2025-11-13 07:32:25'),
(42, 9, 4, '2025-11-13', 'absent', 5, '2025-11-13 07:32:25', '2025-11-13 07:32:25'),
(43, 10, 4, '2025-11-13', 'present', 5, '2025-11-13 07:32:25', '2025-11-13 07:32:25'),
(44, 12, 4, '2025-11-13', 'absent', 5, '2025-11-13 07:32:25', '2025-11-13 07:32:25');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  `description` text DEFAULT NULL,
  `stream` varchar(100) DEFAULT NULL,
  `units` int(11) DEFAULT 0,
  `course_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `class_name`, `course_code`, `created_at`, `status`, `description`, `stream`, `units`, `course_name`) VALUES
(1, 'BUSINESS COMMUNICATION', 'BC101', '2025-09-25 04:11:18', 'active', 'communication skills', 'BUSINESS', 4, NULL),
(2, 'MOBILE APPLICATION', 'M101', '2025-09-25 18:50:57', 'active', 'development skills for applications etc', 'IT & AI', 6, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `course_subject`
--

CREATE TABLE `course_subject` (
  `id` int(11) UNSIGNED NOT NULL,
  `course_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_subjects`
--

CREATE TABLE `course_subjects` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `year_level` int(11) DEFAULT 1,
  `term` int(11) DEFAULT 1,
  `is_mandatory` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_subjects`
--

INSERT INTO `course_subjects` (`id`, `course_id`, `subject_id`, `year_level`, `term`, `is_mandatory`, `created_at`) VALUES
(1, 1, 3, 1, 1, 1, '2025-09-29 11:39:22'),
(2, 1, 2, 1, 1, 1, '2025-09-29 11:39:41');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `receiver_id` int(10) UNSIGNED NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `subject`, `message`, `status`, `created_at`) VALUES
(2, 9, 6, NULL, 'look', 'read', '2025-09-28 21:45:33'),
(3, 6, 9, 'RE: ', 'dont say shit little boy. i no where u live', 'unread', '2025-09-28 21:46:46'),
(4, 4, 5, 'Jujutsu', 'hello', 'unread', '2025-11-08 23:03:34'),
(5, 5, 6, 'Results coming back', 'hello madam', 'unread', '2025-11-12 20:01:38'),
(6, 6, 5, 'English', 'lkijuhyg', 'unread', '2025-11-13 07:18:26'),
(7, 4, 5, 'English', 'cant login', 'unread', '2025-11-13 10:31:38');

-- --------------------------------------------------------

--
-- Table structure for table `parent_child`
--

CREATE TABLE `parent_child` (
  `id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parent_child`
--

INSERT INTO `parent_child` (`id`, `parent_id`, `student_id`, `created_at`) VALUES
(1, 6, 9, '2025-09-18 06:32:55'),
(2, 6, 10, '2025-09-30 18:18:52'),
(3, 6, 9, '2025-11-13 08:44:49'),
(4, 6, 12, '2025-11-13 10:30:45');

-- --------------------------------------------------------

--
-- Table structure for table `parent_students`
--

CREATE TABLE `parent_students` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parent_students`
--

INSERT INTO `parent_students` (`id`, `parent_id`, `student_id`, `created_at`) VALUES
(1, 5, 10, '2025-11-13 08:43:36'),
(2, 5, 15, '2025-11-13 08:43:36'),
(3, 6, 20, '2025-11-13 08:43:36');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL,
  `subject_id` int(11) UNSIGNED NOT NULL,
  `marks` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `teacher_id` int(11) UNSIGNED DEFAULT NULL,
  `max_marks` decimal(5,2) DEFAULT 100.00,
  `grade` varchar(2) DEFAULT NULL,
  `term` int(11) DEFAULT 1,
  `academic_year` varchar(20) DEFAULT NULL,
  `exam_type` enum('test','coursework','final') DEFAULT 'final',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`id`, `student_id`, `subject_id`, `marks`, `created_at`, `teacher_id`, `max_marks`, `grade`, `term`, `academic_year`, `exam_type`, `updated_at`, `remarks`) VALUES
(1, 11, 3, 89, '2025-09-23 21:18:55', NULL, 100.00, 'A', 3, '2025', 'test', '2025-09-29 22:00:11', 'keep it up'),
(2, 9, 2, 55, '2025-09-23 21:19:14', NULL, 100.00, 'D', 2, '2024', 'coursework', '2025-09-29 22:00:31', 'put in more effort'),
(3, 10, 4, 70, '2025-09-23 21:19:25', NULL, 100.00, 'B', 1, '2025', 'final', '2025-09-29 22:02:25', 'well done'),
(4, 12, 3, 45, '2025-11-12 20:00:01', 5, 100.00, 'F', 1, '2025', 'test', '2025-11-12 20:00:14', 'good'),
(5, 11, 4, 20, '2025-11-13 07:27:34', 5, 100.00, 'F', 4, '2025', 'final', '2025-11-13 07:27:34', 'fair'),
(6, 11, 5, 45, '2025-11-13 10:34:15', 5, 100.00, 'F', 2, '2025', 'coursework', '2025-11-13 10:34:15', 'fair');

-- --------------------------------------------------------

--
-- Table structure for table `srms`
--

CREATE TABLE `srms` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `subject1` int(11) NOT NULL,
  `subject2` int(11) NOT NULL,
  `subject3` int(11) NOT NULL,
  `total` int(11) NOT NULL,
  `average` decimal(5,2) NOT NULL,
  `grade` char(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `srms`
--

INSERT INTO `srms` (`id`, `name`, `subject1`, `subject2`, `subject3`, `total`, `average`, `grade`, `created_at`, `updated_at`) VALUES
(0, 'Emmanuel burna', 60, 70, 80, 210, 70.00, 'B', '2025-09-10 07:57:57', '2025-09-10 08:13:35'),
(2, 'Clout Manuel', 48, 33, 40, 121, 40.33, 'F', '2025-09-15 11:27:41', '2025-09-15 11:27:41');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `class` varchar(50) NOT NULL,
  `stream` varchar(50) NOT NULL,
  `contact` varchar(100) NOT NULL,
  `admission_year` year(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) UNSIGNED NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `credits` int(11) DEFAULT 1,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_name`, `subject_code`, `created_at`, `credits`, `description`) VALUES
(1, 'WEB DEVELOPMENT', 'CSDA111', '2025-09-23 20:49:52', 1, 'this is nice'),
(2, 'NETWORKING ', 'CSDA112', '2025-09-23 20:50:57', 1, NULL),
(3, 'JAVA PROGRAMMING', 'CSDA113', '2025-09-23 20:52:06', 1, 'testing'),
(4, 'MATHSMATICS', 'CSDB131', '2025-09-23 20:55:26', 1, 'tring'),
(5, 'Geography', 'CSDA1112', '2025-11-13 09:26:34', 3, '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student','parent') NOT NULL DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `full_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `admission_year` year(4) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `stream` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `full_name`, `email`, `contact`, `admission_year`, `class_id`, `active`, `stream`) VALUES
(4, 'clout', '$2y$10$VqcPlLeVnmSkQNwhDBAtN.OsBS0Y7Rgr9HK5h78wF3PVdJmuU6Jp6', 'admin', '2025-09-16 21:16:15', 'clout burna', 'clout@gmail.com', NULL, NULL, NULL, 1, NULL),
(5, 'burna', '$2y$10$CI05t1o/FWf5X.NNL0axEOWA4JlC0rYLL6fMy5.hnfw7ECOttlKT2', 'teacher', '2025-09-16 22:09:50', 'Burna Manuel', 'burna@gmail.com', NULL, NULL, NULL, 1, NULL),
(6, 'Levi Ackerman', '$2y$10$XPMOXqCw3NUmDQKol9e9guhWK/u7ci1aHd4PuXhOvd18GqKyUkjmu', 'parent', '2025-09-17 06:08:29', 'Levi Ackerman', 'levi@gmail.com', NULL, NULL, NULL, 1, NULL),
(9, 'Tari', '$2y$10$EtTbL619I6ouGbIRKCNyO.F6JlDimoATPOAy8gZJok3oCxaaNhA7W', 'student', '2025-09-17 20:20:35', 'Talemwa Richard', 'roxy3@gmail.com', NULL, '1908', 1, 1, NULL),
(10, 'Yuji', '$2y$10$cUMtJQs.VEdYXULfQo3ESuNjw014mZ70Ug.9JFOEJZ4j8LJD0ICl6', 'student', '2025-09-18 20:12:16', 'Yuji Itadori', 'yuji2@gmail.com', NULL, '2005', 1, 1, NULL),
(11, 'Armin', '$2y$10$TqIYkRd92JWYu1vKVbt38Ou6.Ta3xZZd08kQlQ6Fl5VoEu5W0eM6e', 'student', '2025-09-23 20:58:00', 'Armin toji', 'armin@gmail.com', NULL, '2024', 1, 1, NULL),
(12, 'Zenine', '$2y$10$mrma9TpVI/JCE7Y1dlP9G.7T9x2yd8jdbD.NZJhj6dNWBt6poE2IS', 'student', '2025-09-24 03:46:15', 'Mahoraga Zenine', 'zenine@gmail.com', NULL, '2023', NULL, 1, NULL),
(15, '2', '$2y$10$vcT.waNrcUXA40UigfUQluuxvsz3lFXTmCh2EkEUq1a1WJkf6go8G', 'teacher', '2025-11-08 22:35:16', '9', '55', NULL, NULL, NULL, 0, NULL),
(16, '3', '$2y$10$hhyQMb6vC1xhR.Y8EEpJjOxaxou25IhwEEUTQjB3uSLIaz.ucudaW', 'parent', '2025-11-08 22:35:16', '10', '70', NULL, NULL, NULL, 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`);

--
-- Indexes for table `course_subject`
--
ALTER TABLE `course_subject`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_id` (`course_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `course_subjects`
--
ALTER TABLE `course_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_course_subject` (`course_id`,`subject_id`,`year_level`,`term`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `parent_child`
--
ALTER TABLE `parent_child`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `parent_students`
--
ALTER TABLE `parent_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_parent_student` (`parent_id`,`student_id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_student` (`student_id`),
  ADD KEY `fk_subject` (`subject_id`),
  ADD KEY `results_teacher_fk` (`teacher_id`);

--
-- Indexes for table `srms`
--
ALTER TABLE `srms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`(22)),
  ADD KEY `course_id` (`class_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `course_subject`
--
ALTER TABLE `course_subject`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_subjects`
--
ALTER TABLE `course_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `parent_child`
--
ALTER TABLE `parent_child`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `parent_students`
--
ALTER TABLE `parent_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `course_subjects`
--
ALTER TABLE `course_subjects`
  ADD CONSTRAINT `course_subjects_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parent_child`
--
ALTER TABLE `parent_child`
  ADD CONSTRAINT `parent_child_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parent_child_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `fk_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_teacher_fk` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
