-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 23, 2026 at 12:17 PM
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
-- Database: `edulift_fdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `institutions`
--

CREATE TABLE `institutions` (
  `institution_id` int(11) NOT NULL,
  `institution_name` varchar(200) NOT NULL,
  `institution_type` varchar(50) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `institutions`
--

INSERT INTO `institutions` (`institution_id`, `institution_name`, `institution_type`, `location`, `contact_email`, `contact_phone`, `description`) VALUES
(1, 'University of the Philippines Diliman', 'University', 'Quezon City, Metro Manila', 'osg@up.edu.ph', '02-8981-8500', 'UP Diliman is the flagship campus offering various scholarship programs.'),
(2, 'Ateneo de Manila University', 'University', 'Quezon City, Metro Manila', 'osa@ateneo.edu', '02-8426-6001', 'Ateneo provides financial assistance to qualified students.'),
(3, 'De La Salle University', 'University', 'Manila, Metro Manila', 'dlsu-manila@dlsu.edu.ph', '02-8524-4611', 'DLSU offers scholarship grants to help students achieve their goals.'),
(4, 'Tests', 'College', 'Angeles City, Pampanga', 'testing12334@gmail.com', '12345678910', 'test'),
(5, 'Provider 2 University', 'University', 'Quezon City', 'provider2@test.edu.ph', '12345678910', ''),
(6, 'Provider University', 'University', 'Quezon City', 'providertest@test.edu.ph', '12345678910', 'Test'),
(7, 'Provider University', 'University', 'Quezon City', 'providertest@test.edu.ph', '12345678910', 'Testing Description');

-- --------------------------------------------------------

--
-- Table structure for table `provider_profiles`
--

CREATE TABLE `provider_profiles` (
  `provider_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `provider_profiles`
--

INSERT INTO `provider_profiles` (`provider_id`, `user_id`, `institution_id`, `status`, `created_at`) VALUES
(1, 2, 1, 'verified', '2026-02-11 11:35:52'),
(2, 3, 2, 'verified', '2026-02-11 11:35:52'),
(3, 4, 3, 'verified', '2026-02-11 11:35:52'),
(7, 13, 7, 'verified', '2026-03-20 05:04:38');

-- --------------------------------------------------------

--
-- Table structure for table `scholarships`
--

CREATE TABLE `scholarships` (
  `scholarship_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `scholarship_name` varchar(255) NOT NULL,
  `scholarship_type` varchar(50) DEFAULT NULL,
  `description` text NOT NULL,
  `amount` varchar(100) DEFAULT NULL,
  `education_level` varchar(50) DEFAULT NULL,
  `gpa_requirement` decimal(3,2) DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `application_deadline` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scholarships`
--

INSERT INTO `scholarships` (`scholarship_id`, `provider_id`, `scholarship_name`, `scholarship_type`, `description`, `amount`, `education_level`, `gpa_requirement`, `requirements`, `application_deadline`, `status`, `created_at`) VALUES
(1, 1, 'UP Oblation Scholarship', 'Full Scholarship', 'Full scholarship covering tuition and fees for excellent students.', 'Full Tuition', 'College Undergraduate', 3.50, 'High school diploma, Transcript of Records, Entrance exam results', '2026-03-31', 'active', '2026-02-11 11:36:56'),
(2, 1, 'Socialized Tuition System', 'Partial Scholarship', 'Tuition discount based on family income.', 'Variable', 'College Undergraduate', 2.50, 'Certificate of enrollment, Income documents', '2026-06-30', 'active', '2026-02-11 11:36:56'),
(3, 2, 'Ateneo Scholarship Program', 'Full Scholarship', 'Full tuition for exceptional students with financial need.', 'Full Tuition', 'College Undergraduate', 3.70, 'Application form, Transcript, Income documents', '2026-04-15', 'active', '2026-02-11 11:36:56'),
(4, 3, 'DLSU Gokongwei Scholarship', 'Full Scholarship', 'Full tuition plus stipend for Business, Engineering, Science.', 'PHP 80,000', 'College Undergraduate', 3.80, 'Application form, High school grades, Essay', '2026-05-01', 'active', '2026-02-11 11:36:56'),
(5, 3, 'Br. Andrew Gonzalez Scholarship', 'Partial Scholarship', 'Partial tuition for students with good academic standing.', '50% Tuition', 'College Undergraduate', 3.30, 'Application form, Transcript, Good Moral Certificate', '2026-05-30', 'active', '2026-02-11 11:36:56'),
(9, 7, 'Test Scholarship', 'Partial Scholarship', 'Test.', 'PHP 50,000', 'College Undergraduate', 2.00, 'Test', '2026-03-20', 'active', '2026-03-21 12:00:41');

-- --------------------------------------------------------

--
-- Table structure for table `scholarship_fields`
--

CREATE TABLE `scholarship_fields` (
  `field_id` int(11) NOT NULL,
  `scholarship_id` int(11) NOT NULL,
  `field_of_study` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scholarship_fields`
--

INSERT INTO `scholarship_fields` (`field_id`, `scholarship_id`, `field_of_study`) VALUES
(1, 1, 'All Fields'),
(2, 2, 'All Fields'),
(3, 3, 'All Fields'),
(4, 4, 'Business'),
(5, 4, 'Engineering'),
(6, 4, 'Science'),
(7, 5, 'All Fields'),
(15, 9, 'All Fields');

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `education_level` varchar(50) DEFAULT NULL,
  `field_of_interest` varchar(100) DEFAULT NULL,
  `gpa` decimal(3,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_profiles`
--

INSERT INTO `student_profiles` (`student_id`, `user_id`, `full_name`, `contact_phone`, `location`, `education_level`, `field_of_interest`, `gpa`, `created_at`) VALUES
(1, 5, 'Juan Dela Cruz', '09171234567', 'Manila, Metro Manila', 'Senior High School', 'Engineering', 3.85, '2026-02-11 11:36:23'),
(2, 6, 'Maria Santos', '09189876543', 'Angeles City, Pampanga', 'Senior High School', 'Medicine', 3.95, '2026-02-11 11:36:23'),
(5, 11, 'Student Test', NULL, 'Angeles City, Pampanga', NULL, 'Engineering', NULL, '2026-03-20 04:46:13');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `user_type`, `created_at`) VALUES
(1, 'admin', 'admin@edulift.ph', 'admin123', 'admin', '2026-02-11 11:31:00'),
(2, 'up_diliman', 'scholarships@up.edu.ph', 'provider123', 'provider', '2026-02-11 11:33:16'),
(3, 'ateneo', 'scholarships@ateneo.edu', 'provider123', 'provider', '2026-02-11 11:33:16'),
(4, 'dlsu', 'scholarships@dlsu.edu.ph', 'provider123', 'provider', '2026-02-11 11:33:16'),
(5, 'juan_delacruz', 'juan.delacruz@gmail.com', 'student123', 'student', '2026-02-11 11:34:46'),
(6, 'incougar_smith', 'incougar.smith@gmail.com', 'student123', 'student', '2026-02-11 11:34:46'),
(11, 'Student Test', 'studenttest@gmail.com', 'studenttest1', 'student', '2026-03-20 04:46:13'),
(13, 'Provider Test', 'providertest@test.edu.ph', 'providertest12', 'provider', '2026-03-20 05:04:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `institutions`
--
ALTER TABLE `institutions`
  ADD PRIMARY KEY (`institution_id`);

--
-- Indexes for table `provider_profiles`
--
ALTER TABLE `provider_profiles`
  ADD PRIMARY KEY (`provider_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `institution_id` (`institution_id`);

--
-- Indexes for table `scholarships`
--
ALTER TABLE `scholarships`
  ADD PRIMARY KEY (`scholarship_id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `scholarship_fields`
--
ALTER TABLE `scholarship_fields`
  ADD PRIMARY KEY (`field_id`),
  ADD KEY `scholarship_id` (`scholarship_id`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`student_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `institutions`
--
ALTER TABLE `institutions`
  MODIFY `institution_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `provider_profiles`
--
ALTER TABLE `provider_profiles`
  MODIFY `provider_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `scholarships`
--
ALTER TABLE `scholarships`
  MODIFY `scholarship_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `scholarship_fields`
--
ALTER TABLE `scholarship_fields`
  MODIFY `field_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `student_profiles`
--
ALTER TABLE `student_profiles`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `provider_profiles`
--
ALTER TABLE `provider_profiles`
  ADD CONSTRAINT `provider_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `provider_profiles_ibfk_2` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`institution_id`);

--
-- Constraints for table `scholarships`
--
ALTER TABLE `scholarships`
  ADD CONSTRAINT `scholarships_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `provider_profiles` (`provider_id`);

--
-- Constraints for table `scholarship_fields`
--
ALTER TABLE `scholarship_fields`
  ADD CONSTRAINT `scholarship_fields_ibfk_1` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`scholarship_id`);

--
-- Constraints for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
