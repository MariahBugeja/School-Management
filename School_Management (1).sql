-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Jul 30, 2025 at 09:38 PM
-- Server version: 5.7.39
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `School_Management`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `user_id`) VALUES
(2, 1),
(1, 7),
(3, 21);

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `assignment_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text,
  `file_path` varchar(255) DEFAULT NULL,
  `deadline` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`assignment_id`, `class_id`, `teacher_id`, `title`, `description`, `file_path`, `deadline`) VALUES
(1, 4, 7, 'Java Task 1 - Poker Card game', 'You are required to make a full poker card game, fully logical with the traditional set rules of texas holdem poker.', 'uploads/assignments/1753632009_Specimen20Paper20-20AM200720Computing20201920Task20220Marking20Scheme.pdf', '2025-10-23'),
(2, 4, 7, 'Task 2 - Java Completion Project', 'Continuation of the Poker project 2.', 'uploads/assignments/1753632353_Specimen20Paper20-20AM200720Computing20201920Task20220Marking20Scheme.pdf', '2025-12-26'),
(3, 4, 7, 'Test Assignment', 'Test Assignment', 'uploads/assignments/1753633084_Specimen20Paper20-20AM200720Computing20201920Task20220Marking20Scheme.pdf', '2025-11-07');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent','Late') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `class_id`, `student_id`, `date`, `status`) VALUES
(1, 4, 2, '2025-07-31', 'Absent'),
(2, 4, 3, '2025-07-31', 'Present'),
(3, 4, 1, '2025-07-31', 'Late'),
(4, 4, 2, '2025-07-26', 'Present'),
(5, 4, 3, '2025-07-26', 'Absent'),
(6, 4, 1, '2025-07-26', 'Present'),
(7, 4, 2, '2025-07-14', 'Present'),
(8, 4, 3, '2025-07-14', 'Present'),
(9, 4, 1, '2025-07-14', 'Present'),
(10, 4, 2, '2025-07-23', 'Present'),
(11, 4, 3, '2025-07-23', 'Present'),
(12, 4, 1, '2025-07-23', 'Absent'),
(13, 4, 2, '2025-07-08', 'Present'),
(14, 4, 3, '2025-07-08', 'Present'),
(15, 4, 1, '2025-07-08', 'Present'),
(19, 4, 2, '2025-07-15', 'Present'),
(20, 4, 3, '2025-07-15', 'Present'),
(21, 4, 1, '2025-07-15', 'Present'),
(22, 4, 2, '2025-07-27', 'Absent'),
(23, 4, 3, '2025-07-27', 'Present'),
(24, 4, 1, '2025-07-27', 'Present');

-- --------------------------------------------------------

--
-- Table structure for table `Classes`
--

CREATE TABLE `Classes` (
  `class_id` int(11) NOT NULL,
  `class_name` varchar(255) NOT NULL,
  `teacher_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `Classes`
--

INSERT INTO `Classes` (`class_id`, `class_name`, `teacher_id`) VALUES
(1, 'Maths Class 1', 1),
(2, 'English', 2),
(3, 'Physics 2', 2),
(4, 'Computing', 7);

-- --------------------------------------------------------

--
-- Table structure for table `class_student`
--

CREATE TABLE `class_student` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `class_student`
--

INSERT INTO `class_student` (`id`, `class_id`, `student_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 2, 3),
(4, 4, 2),
(5, 4, 3),
(6, 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `grade_id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `marks` int(11) NOT NULL,
  `status` enum('Pass','Fail') GENERATED ALWAYS AS (if((`marks` >= 50),'Pass','Fail')) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`grade_id`, `submission_id`, `marks`) VALUES
(9, 1, 51),
(12, 6, 49),
(13, 5, 82);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_of_birth` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `date_of_birth`) VALUES
(1, 12, '2008-05-13'),
(2, 13, NULL),
(3, 18, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `submission`
--

CREATE TABLE `submission` (
  `submission_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `submitted_on` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `submission`
--

INSERT INTO `submission` (`submission_id`, `assignment_id`, `student_id`, `file_path`, `submitted_on`) VALUES
(1, 3, 1, 'uploads/submissions/1753633960_Specimen20Paper20-20AM200720Computing20201920Task20220Marking20Scheme.pdf', '2025-07-27'),
(5, 1, 1, 'uploads/submissions/1753638717_Specimen20Paper20-20AM200720Computing20201920Task20220Marking20Scheme.pdf', '2025-07-27'),
(6, 2, 1, 'uploads/submissions/1753638717_Specimen20Paper20-20AM200720Computing20201920Task20220Marking20Scheme.pdf', '2025-07-27');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`teacher_id`, `user_id`) VALUES
(1, 11),
(2, 14),
(3, 15),
(4, 16),
(5, 17),
(6, 19),
(7, 20);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `email`) VALUES
(1, 'adminuser', '*2885FF2B3FEB66C3AF1F0411561567CBAC7A92DC', 'admin', 'admin@example.com'),
(2, 'Mariahbugeja12@gmail.com', '$2y$10$pCF2Pk44IrcMJJqvla8WkuuDkvSG6Mn2QQchNb5/IVuTv52OclEuS', 'student', 'fevds@gmail.com'),
(7, 'Admin1', '$2y$10$A/PQTunOYG8V4Cec6H7lAuhJKkhAl0RkMiqtBrv0QXOWlqKsOU4SG', 'admin', 'Admin20021@gmail.com'),
(11, 'mar123', '$2y$10$uoyVM1U9woToOwGmm1rFU.yh6X3ms1DwVtF9e/OOuB28VdP6gR3Ku', 'teacher', 'mar123@gmail.com'),
(12, 'st123', '$2y$10$u0v52kRTi9pT2FJv6NObjeoJK5OGUiYAlB2V0rlkpvfzIGJr965oG', 'student', 'st123@gmail.com'),
(13, 'Maya', '$2y$10$3/q7lccOJH0Xr.eK22cMwuc5aJsShR87wtSBszTumSeGLjT9G27Qy', 'student', 'maya@gmail.com'),
(14, 'JMarf', '$2y$10$hnUovHPeAk9Lr6IMMwjiAOhkZPFcybxXslc6GKWJkeS2dB0UTb78q', 'teacher', 'eefd@gmail.com'),
(15, 'WWWW.', '$2y$10$2nZLzyCULnZmxu8mH2GD8ek4/STgzMK9ri1BnSjTFnT1wxKzbvC1O', 'teacher', 'WWW@FMAOL.COM'),
(16, 'ASD', '$2y$10$BDSnyBhppdtAPob0vMBr3ukJByXMBspcavzwEogldM3hM/CMu3oT2', 'teacher', 'TEST123333@GMAIL.COM'),
(17, 'okpop', '$2y$10$wZpLuHh15tjFWcxjt.Pz0.N.9XHksK0E4rNQcThkFbe67QhFT2EKu', 'teacher', 'okpop@gmail.c'),
(18, 'pop213@gmai', '$2y$10$MXybENkWDIuNY/VCfHU2M.xoUW36ptPyMacQAsKv1RFOXO2MSHvR2', 'student', 'pop213@gmail.com'),
(19, '9999', '$2y$10$olfJrixW7JG0xujwLoaGnuwS1pjE/Ga3smvoD5ya.PqEt7OuXvK2G', 'teacher', '9999@gmail.com'),
(20, 'teacher', '$2y$10$aQNlbWVc5ZzkxZDmZknTkeW.I1sIghGHWZO.lR6BsZxVV3q3DsCEu', 'teacher', 'teacher@gmail.com'),
(21, 'subadmin', '$2y$10$ts3cZPaMnj8xYoVl6rY16u6/t2kRFAfxjMSHHbAVm4HNVOjshNz2u', 'admin', 'subadmin@gmail.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `fk_assignments_class` (`class_id`),
  ADD KEY `fk_assignments_teacher` (`teacher_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `unique_attendance` (`class_id`,`student_id`,`date`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `Classes`
--
ALTER TABLE `Classes`
  ADD PRIMARY KEY (`class_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `class_student`
--
ALTER TABLE `class_student`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`grade_id`),
  ADD UNIQUE KEY `submission_id` (`submission_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `submission`
--
ALTER TABLE `submission`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`teacher_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `Classes`
--
ALTER TABLE `Classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `class_student`
--
ALTER TABLE `class_student`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `submission`
--
ALTER TABLE `submission`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`),
  ADD CONSTRAINT `fk_assignments_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assignments_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `Classes`
--
ALTER TABLE `Classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`);

--
-- Constraints for table `class_student`
--
ALTER TABLE `class_student`
  ADD CONSTRAINT `class_student_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `fk_grades_submission` FOREIGN KEY (`submission_id`) REFERENCES `submission` (`submission_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `submission` (`submission_id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `submission`
--
ALTER TABLE `submission`
  ADD CONSTRAINT `submission_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`assignment_id`),
  ADD CONSTRAINT `submission_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
