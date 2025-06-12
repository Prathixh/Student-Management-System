-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 08, 2025 at 10:57 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dbmsnew`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `courses_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `courses_id` int(11) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `credit` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`courses_id`, `course_name`, `course_code`, `faculty_id`, `department_id`, `department_name`, `credit`, `semester`, `description`, `created_at`) VALUES
(1, 'DATAWAREHOUSING', 'GDB1221', 10, 9, '', 3, 2, 'MAIN', '2025-05-03 13:21:11'),
(2, 'DBMS', 'QW0909', 15, 9, '', 4, 1, '..', '2025-05-04 04:22:47'),
(3, 'MBA MATHS', 'MB1021', 12, 10, '', 4, 3, 'MAIN PAPER', '2025-05-07 07:38:16');

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `department_code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `no_of_students` int(11) DEFAULT 0,
  `no_of_faculty` int(11) DEFAULT 0,
  `hod_name` varchar(100) DEFAULT NULL,
  `established_year` year(4) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `department_name`, `department_code`, `description`, `no_of_students`, `no_of_faculty`, `hod_name`, `established_year`, `contact_email`) VALUES
(9, 'AI', 'AIDS', 'ARTIFICIAL INTELLIGENCE', 14, 0, 'SELVI A', '2020', 'ai@mkce.ac.in'),
(10, 'ECE', 'ECE', 'ELECTRONICS ENGINEERING', 0, 0, 'BHARATHI S', '2000', 'ece@mkce.ac.in'),
(11, 'CSE', 'CSE', 'COMPUTER SCIENCE', 1, 0, 'SANDEL S', '2004', 'cse@mkce.ac.in'),
(12, 'EEE', 'EEE', 'ELECTRICAL ENG', 0, 0, 'RUDHRA M', '2003', 'eee@mkce.ac.in'),
(13, 'IT', 'IT01', 'HI', 0, 0, 'RAJU', '2004', 'it@mkce.ac.in'),
(14, 'MBA', 'MB01', '..', 0, 0, 'SACHIN MR', '2006', 'mba@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `faculty_name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`faculty_id`, `users_id`, `faculty_name`, `phone`, `qualification`, `department_id`, `email`) VALUES
(9, 21, 'BHARANI NAYAGI S', '9360979902', 'M.E', 9, ''),
(10, 23, 'jayaganesh', '9876543210', 'be', 9, 'jaya@gmail.com'),
(11, 24, 'SARASWATHI S', '8527419630', 'B.SC ', 11, 'saraswathi@gmail.com'),
(12, 25, 'LEO DAS', '7418529630', 'M.E', 10, 'leo@gamil.com'),
(13, 28, 'jegan', '9360979902', 'M.E', 9, 'jegan@gmail.com'),
(14, 29, 'KRITHIKA S', '9876543210', 'B.tech', 12, 'krithika@gmail.com'),
(15, 30, 'SARATHA S', '9360979902', 'B.SC ', 9, 'saratha@gmail.com'),
(16, 31, 'ROSHAN M', '9360979902', 'M.E', 14, 'roshan@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `marks`
--

CREATE TABLE `marks` (
  `marks_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `courses_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `marks_obtained` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset`
--

CREATE TABLE `password_reset` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `dob` date NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `department_id` varchar(100) DEFAULT NULL,
  `year` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `users_id`, `dob`, `address`, `phone`, `full_name`, `email`, `password`, `course`, `department_id`, `year`) VALUES
(22, 34, '2005-02-01', '16,valluvar nagar, velayuthampalayam', '8527419630', 'ramana', 'ramana@gmail.com', NULL, 'CSE (AIML)', '9', '2nd'),
(23, 36, '2005-01-01', '106,bye pass road, thalavapalayam ', '9873216540', 'sarvesh vr', 'sarveshvr01@gmail.com', NULL, 'CSE (AIML)', '9', '2nd');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `users_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','faculty','student') NOT NULL,
  `approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `faculty_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`users_id`, `email`, `password`, `role`, `approved`, `created_at`, `faculty_id`, `student_id`) VALUES
(6, 'admin@admin.com', '25f9e794323b453885f5181f1b624d0b', 'admin', 1, '2025-05-01 11:15:52', NULL, NULL),
(8, 'student@student.com', '25f9e794323b453885f5181f1b624d0b', 'student', 1, '2025-05-01 11:15:52', NULL, NULL),
(20, 'faculty@gmail.com', '25f9e794323b453885f5181f1b624d0b', 'faculty', 1, '2025-05-02 08:09:01', NULL, NULL),
(21, 'bharani@gmail.com', '25f9e794323b453885f5181f1b624d0b', 'faculty', 1, '2025-05-02 08:54:32', 9, NULL),
(23, 'jaya@gmail.com', '25f9e794323b453885f5181f1b624d0b', 'faculty', 1, '2025-05-02 08:57:06', 10, NULL),
(24, 'saraswathi@gmail.com', '25f9e794323b453885f5181f1b624d0b', 'faculty', 1, '2025-05-02 13:05:03', 11, NULL),
(25, 'leo@gamil.com', '25f9e794323b453885f5181f1b624d0b', 'faculty', 1, '2025-05-02 13:05:49', 12, NULL),
(28, 'jegan@gmail.com', '25f9e794323b453885f5181f1b624d0b', 'faculty', 1, '2025-05-03 13:03:03', 13, NULL),
(29, 'krithika@gmail.com', '25f9e794323b453885f5181f1b624d0b', 'faculty', 1, '2025-05-03 13:27:42', 14, NULL),
(30, 'saratha@gmail.com', '25f9e794323b453885f5181f1b624d0b', 'faculty', 1, '2025-05-04 04:19:58', 15, NULL),
(31, 'roshan@gmail.com', '25f9e794323b453885f5181f1b624d0b', 'faculty', 1, '2025-05-07 07:38:59', NULL, NULL),
(32, '927623BAM043@mkce.ac.in', '$2y$10$7id9VkDgKBlbVkVKwPM/F.05Iysx55/Rmpa.2IRmWnvofH4/1BEEy', 'student', 0, '2025-05-07 10:21:28', NULL, NULL),
(34, 'ramana@gmail.com', '$2y$10$wLBnZYRoggFVT7khzlS./Ohb8dGR2RS4gM8axxA4RADCy6BJUD5L6', 'student', 1, '2025-05-07 10:22:23', NULL, NULL),
(36, 'sarveshvr01@gmail.com', '$2y$10$7gwe4r81MxsFlZyxHHj5WuUcOTt94mLa666EaJyzAWx2n0vwo.5Za', 'student', 0, '2025-05-07 10:24:07', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`courses_id`,`attendance_date`),
  ADD KEY `courses_id` (`courses_id`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`courses_id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `fk_faculty` (`faculty_id`),
  ADD KEY `fk_department` (`department_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_code` (`department_code`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`faculty_id`),
  ADD KEY `user_id` (`users_id`);

--
-- Indexes for table `marks`
--
ALTER TABLE `marks`
  ADD PRIMARY KEY (`marks_id`),
  ADD KEY `fk_marks_student` (`student_id`),
  ADD KEY `fk_marks_course` (`courses_id`),
  ADD KEY `fk_marks_faculty` (`faculty_id`);

--
-- Indexes for table `password_reset`
--
ALTER TABLE `password_reset`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_id` (`users_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`users_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `courses_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `marks`
--
ALTER TABLE `marks`
  MODIFY `marks_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `users_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`courses_id`) REFERENCES `courses` (`courses_id`),
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`);

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `fk_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`) ON DELETE CASCADE;

--
-- Constraints for table `marks`
--
ALTER TABLE `marks`
  ADD CONSTRAINT `fk_marks_course` FOREIGN KEY (`courses_id`) REFERENCES `courses` (`courses_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_marks_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_marks_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`),
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
