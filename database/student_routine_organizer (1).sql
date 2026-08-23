-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 22, 2026 at 06:26 AM
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
-- Database: `student_routine_organizer`
--

-- --------------------------------------------------------

--
-- Table structure for table `achievements`
--

CREATE TABLE `achievements` (
  `achievement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `badge_name` varchar(100) NOT NULL,
  `badge_icon` varchar(10) NOT NULL DEFAULT '?',
  `description` varchar(255) NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `achievements`
--

INSERT INTO `achievements` (`achievement_id`, `user_id`, `badge_name`, `badge_icon`, `description`, `earned_at`) VALUES
(1, 1, 'First Workout', '🎯', 'Logged your very first exercise!', '2026-08-19 06:12:14'),
(2, 1, '7-Day Streak', '🔥', 'Exercised 7 days in a row!', '2026-08-19 06:12:14'),
(3, 1, '1000 Calories', '💪', 'Burned over 1000 total calories!', '2026-08-19 06:12:14');

-- --------------------------------------------------------

--
-- Table structure for table `badge_types`
--

CREATE TABLE `badge_types` (
  `badge_type_id` int(11) NOT NULL,
  `reward_code` varchar(50) NOT NULL,
  `reward_name` varchar(100) NOT NULL,
  `reward_description` varchar(255) NOT NULL,
  `requirement` int(10) UNSIGNED NOT NULL,
  `tree_tier` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `reward_type` enum('Badge','Decoration') NOT NULL,
  `slot` enum('Hat','Glasses','Clothes','Shoes','Background') DEFAULT NULL,
  `reward_asset` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `badge_types`
--

INSERT INTO `badge_types` (`badge_type_id`, `reward_code`, `reward_name`, `reward_description`, `requirement`, `tree_tier`, `reward_type`, `slot`, `reward_asset`) VALUES
(1, 'streak_3', '3-Day Streak', 'Complete the same habit for 3 consecutive days.', 3, 1, 'Badge', NULL, '🏅'),
(2, 'streak_7', '7-Day Streak', 'Complete the same habit for 7 consecutive days.', 7, 2, 'Badge', NULL, '🏆'),
(3, 'checkins_10', 'Ten Check-ins', 'Complete any 10 habit check-ins.', 10, 2, 'Badge', NULL, '⭐');

-- --------------------------------------------------------

--
-- Table structure for table `diary_entries`
--

CREATE TABLE `diary_entries` (
  `diary_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` text NOT NULL,
  `mood` varchar(30) NOT NULL,
  `weather` varchar(20) DEFAULT NULL,
  `entry_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_favorite` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `diary_entries`
--

INSERT INTO `diary_entries` (`diary_id`, `user_id`, `title`, `content`, `mood`, `entry_date`, `created_at`, `updated_at`, `is_favorite`) VALUES
(4, 4, 'rge', 'ger', 'Sad', '2026-08-21', '2026-08-21 04:04:00', '2026-08-21 04:04:00', 0),
(5, 4, 'dcddwe', '<span style=\"background-color: rgb(255, 241, 168);\">qwddwhdbwdqhwhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhh</span><div><span style=\"background-color: rgb(255, 241, 168);\"><br></span></div><div><span style=\"background-color: rgb(255, 241, 168);\"><br></span></div><div><span style=\"background-color: rgb(255, 241, 168);\"><br></span></div><div><span style=\"background-color: rgb(255, 241, 168);\"><br></span></div><div><span style=\"background-color: rgb(255, 241, 168);\"><br></span></div><div><span style=\"background-color: rgb(255, 241, 168);\"><br></span></div><div><span style=\"background-color: rgb(255, 241, 168);\"><br></span></div><div><span style=\"background-color: rgb(255, 241, 168);\"><br></span></div><div><span style=\"background-color: rgb(255, 241, 168);\"><br></span></div><div><span style=\"background-color: rgb(255, 241, 168);\"><br></span></div><div><span style=\"background-color: rgb(255, 241, 168);\"><br></span></div><div><span style=\"background-color: rgb(255, 241, 168);\"><br></span></div><div><span style=\"background-color: rgb(255, 241, 168);\"><br></span></div>', 'Calm', '2026-08-20', '2026-08-21 12:26:14', '2026-08-21 12:26:14', 0),
(6, 4, 'ggtrtrt', '<!--DIARY_RICH_TEXT_V1--><u>ferferfefrf </u>jj<p><br></p><blockquote><blockquote><blockquote><blockquote><p style=\"text-align: justify\"><u>jggggg</u></p></blockquote></blockquote></blockquote></blockquote><p><u><br></u></p><p><u><br></u></p>', 'Happy', '2026-08-21', '2026-08-21 13:03:40', '2026-08-21 13:03:40', 0),
(7, 4, 'test', '<!--DIARY_RICH_TEXT_V1-->dsdds plain text<p><b>dd</b></p><p><b><i>dd</i></b></p><p><b><i><u><span style=\"font-family: Times New Roman\">jqtly</span></u></i></b><b><i><u><span style=\"font-family: Verdana\"></span></u></i></b></p><p><span style=\"font-size: 32px\">hh</span></p><p><span style=\"font-size: 32px\"><span style=\"color: #667653\">jjj</span></span></p><p><span style=\"font-size: 32px\"><span style=\"color: #667653\"><br></span></span></p><p><span style=\"font-size: 32px\"><span style=\"color: #667653; background-color: #d8e7f3\">uuu</span></span></p><p style=\"text-align: right\"><span style=\"font-size: 32px\"><span style=\"color: #667653; background-color: #d8e7f3\">oo</span></span></p><p style=\"text-align: left\"><ul><li>fdffg</li></ul><ol><li>hjjhj</li></ol><blockquote>ghfgh (quote)</blockquote></p>', 'Calm', '2026-08-21', '2026-08-21 13:14:11', '2026-08-21 13:14:11', 0),
(12, 4, 'ferf', '<!--DIARY_RICH_TEXT_V1-->feferfrf🌈☕', 'Calm', '2026-08-21', '2026-08-21 18:17:31', '2026-08-21 18:17:31', 0),
(13, 4, 'ewf', '<!--DIARY_RICH_TEXT_V1-->fsedfsf<p>gfer</p><br><p></p><figure data-diary-object=\"drawing\" data-diary-x=\"20.6\" data-diary-y=\"65.7\" data-diary-width=\"35\" data-diary-rotation=\"0\"><img src=\"/student-routine-organizer/uploads/diary/user_4/6a24eaa1492660430fe2f33fa18ce45a.png\" alt=\"Journal drawing\"></figure>', 'Calm', '2026-08-21', '2026-08-21 20:18:11', '2026-08-22 04:19:29', 1),
(14, 4, 'THUHSUHD', '<!--DIARY_RICH_TEXT_V1--><b><span style=\"font-family: Arial\"><span style=\"color: #315a7d; background-color: #d8e7f3\">rwere</span></span></b><p><ul><li><br></li><li><br></li></ul><ol><li><br></li><li>r</li></ol><figure data-diary-object=\"drawing\" data-diary-x=\"82.5\" data-diary-y=\"73.2\" data-diary-width=\"35\" data-diary-rotation=\"0\"><img src=\"/student-routine-organizer/uploads/diary/user_4/ee243d5ca012e4bf9db96e1a136f4de1.png\" alt=\"Journal drawing\"></figure></p>', 'Stressed', '2026-08-22', '2026-08-22 03:33:10', '2026-08-22 03:33:10', 0);

-- --------------------------------------------------------

--
-- Table structure for table `diary_monthly_reflections`
--

CREATE TABLE `diary_monthly_reflections` (
  `reflection_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reflection_month` date NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `diary_monthly_reflections`
--

INSERT INTO `diary_monthly_reflections` (`reflection_id`, `user_id`, `reflection_month`, `content`, `created_at`, `updated_at`) VALUES
(1, 4, '2026-08-01', 'gggg', '2026-08-22 04:23:23', '2026-08-22 04:23:23'),
(3, 4, '2026-07-01', 'dgf❤️', '2026-08-22 04:24:43', '2026-08-22 04:24:43');

-- --------------------------------------------------------

--
-- Table structure for table `exercise`
--

CREATE TABLE `exercise` (
  `exercise_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `duration` int(11) NOT NULL COMMENT 'duration in minutes',
  `calories_burned` int(11) NOT NULL,
  `exercise_date` date NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exercise`
--

INSERT INTO `exercise` (`exercise_id`, `user_id`, `activity_type`, `duration`, `calories_burned`, `exercise_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'Jogging', 35, 280, '2026-08-19', 'Morning run around campus', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(2, 1, 'Gym', 50, 380, '2026-08-19', 'Upper body and core workout', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(3, 1, 'Yoga', 30, 120, '2026-08-18', 'Evening stretching session', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(4, 1, 'Cycling', 45, 320, '2026-08-18', 'Cycled to the library and back', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(5, 1, 'Swimming', 40, 350, '2026-08-17', 'Freestyle laps at university pool', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(6, 1, 'Jogging', 30, 250, '2026-08-16', 'Interval training at the track', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(7, 1, 'Badminton', 60, 420, '2026-08-16', 'Doubles match with friends', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(8, 1, 'Gym', 65, 450, '2026-08-15', 'Leg day — squats and deadlifts', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(9, 1, 'Walking', 40, 150, '2026-08-15', 'Walk through the botanical garden', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(10, 1, 'Jogging', 25, 210, '2026-08-14', 'Quick morning jog', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(11, 1, 'Cycling', 55, 380, '2026-08-13', 'Long ride along the riverside', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(12, 1, 'Yoga', 45, 140, '2026-08-13', 'Power yoga class', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(13, 1, 'Swimming', 50, 400, '2026-08-12', 'Butterfly and backstroke drills', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(14, 1, 'Gym', 55, 400, '2026-08-11', 'Push day — chest and shoulders', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(15, 1, 'Football', 90, 650, '2026-08-11', 'Friendly match at campus field', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(16, 1, 'Jogging', 40, 320, '2026-08-10', '5K run — personal best attempt', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(17, 1, 'Badminton', 45, 310, '2026-08-09', 'Singles practice', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(18, 1, 'Cycling', 35, 260, '2026-08-08', 'Commute to campus', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(19, 1, 'Gym', 60, 420, '2026-08-07', 'Full body session', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(20, 1, 'Yoga', 35, 110, '2026-08-06', 'Morning meditation and flow', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(21, 1, 'Swimming', 45, 370, '2026-08-05', 'Endurance swim — 2km', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(22, 1, 'Jogging', 30, 240, '2026-08-04', 'Easy jog with music', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(23, 1, 'Walking', 50, 180, '2026-08-03', 'Hiking trail near campus', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(24, 1, 'Gym', 70, 500, '2026-08-02', 'Heavy lifting day', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(25, 1, 'Badminton', 50, 350, '2026-08-01', 'Tournament practice', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(26, 1, 'Cycling', 60, 420, '2026-07-31', 'Hill climbing route', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(27, 1, 'Football', 75, 550, '2026-07-30', 'Inter-faculty game', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(28, 1, 'Jogging', 35, 270, '2026-07-29', 'Tempo run', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(29, 1, 'Swimming', 35, 300, '2026-07-28', 'Recovery swim', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(30, 1, 'Gym', 45, 350, '2026-07-27', 'Arms and abs', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(31, 1, 'Yoga', 40, 130, '2026-07-26', 'Flexibility focus', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(32, 1, 'Jogging', 45, 360, '2026-07-25', 'Long run — 8km', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(33, 1, 'Cycling', 40, 290, '2026-07-24', 'Casual evening ride', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(34, 1, 'Badminton', 55, 380, '2026-07-23', 'Club tournament', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(35, 1, 'Gym', 50, 370, '2026-07-22', 'Pull day — back and biceps', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(36, 1, 'Walking', 60, 200, '2026-07-21', 'Nature walk with friends', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(37, 1, 'Swimming', 40, 340, '2026-07-20', 'Mixed strokes session', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(38, 2, 'Yoga', 40, 150, '2026-08-19', 'Evening relaxation session', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(39, 2, 'Cycling', 50, 300, '2026-08-18', 'Cycled to campus and back', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(40, 2, 'Jogging', 30, 230, '2026-08-16', 'Morning jog', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(41, 2, 'Swimming', 35, 280, '2026-08-14', 'Pool session', '2026-08-19 06:12:14', '2026-08-19 06:12:14');

-- --------------------------------------------------------

--
-- Table structure for table `fitness_profile`
--

CREATE TABLE `fitness_profile` (
  `profile_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `height_cm` decimal(5,1) DEFAULT 170.0,
  `weight_kg` decimal(5,1) DEFAULT 65.0,
  `daily_calorie_goal` int(11) DEFAULT 500,
  `daily_step_goal` int(11) DEFAULT 10000,
  `current_steps` int(11) DEFAULT 0,
  `water_intake_ml` int(11) DEFAULT 0,
  `sleep_hours` decimal(3,1) DEFAULT 0.0,
  `steps_date` date DEFAULT NULL COMMENT 'date the current_steps value belongs to',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fitness_profile`
--

INSERT INTO `fitness_profile` (`profile_id`, `user_id`, `height_cm`, `weight_kg`, `daily_calorie_goal`, `daily_step_goal`, `current_steps`, `water_intake_ml`, `sleep_hours`, `steps_date`, `updated_at`) VALUES
(1, 1, 175.0, 72.5, 500, 10000, 6500, 1500, 7.5, '2026-08-19', '2026-08-19 06:12:14'),
(2, 4, 170.0, 65.0, 500, 10000, 0, 0, 0.0, '2026-08-22', '2026-08-22 03:44:27');

-- --------------------------------------------------------

--
-- Table structure for table `habits`
--

CREATE TABLE `habits` (
  `habit_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `habit_name` varchar(100) NOT NULL,
  `habit_description` text DEFAULT NULL,
  `target_frequency` int(10) UNSIGNED NOT NULL,
  `frequency_type` enum('Daily','Weekly','Monthly') NOT NULL,
  `start_date` date NOT NULL,
  `status` enum('Active','Paused','Completed','Archived') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `habit_badges`
--

CREATE TABLE `habit_badges` (
  `badge_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `habit_id` int(11) DEFAULT NULL,
  `badge_type_id` int(11) NOT NULL,
  `earned_date` date NOT NULL,
  `is_equipped` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `habit_categories`
--

CREATE TABLE `habit_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `category_icon` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `habit_categories`
--

INSERT INTO `habit_categories` (`category_id`, `category_name`, `category_icon`) VALUES
(1, 'Health', 'heart'),
(2, 'Study', 'book'),
(3, 'Fitness', 'dumbbell'),
(4, 'Lifestyle', 'leaf');

-- --------------------------------------------------------

--
-- Table structure for table `habit_logs`
--

CREATE TABLE `habit_logs` (
  `log_id` int(11) NOT NULL,
  `habit_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `log_time` time NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT 1,
  `log_note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','admin') NOT NULL DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Ali Hassan', 'ali@example.com', 'password123', 'student', '2026-08-19 06:12:14'),
(2, 'Siti Aminah', 'siti@example.com', 'password123', 'student', '2026-08-19 06:12:14'),
(3, 'Admin User', 'admin@example.com', 'password123', 'admin', '2026-08-19 06:12:14'),
(4, 'Tan Hui Min', 'huimin20@1utar.my', '$2y$10$xVAFdYoSnBcj8X3Tt5bxKu2E9LMncZmz.hlpCn4b6NwPeGJ9VZCy2', 'student', '2026-08-19 06:30:54'),
(5, 'Tan Hui Min', 'tanhuimin0529@gmail.com', '$2y$10$9TM3rc7QkJ7dfyUcVBiF/u4gcGv0WPcXgUHi0H46mILfB0Pqs5Fzu', 'admin', '2026-08-19 06:55:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`achievement_id`),
  ADD UNIQUE KEY `unique_badge_per_user` (`user_id`,`badge_name`);

--
-- Indexes for table `badge_types`
--
ALTER TABLE `badge_types`
  ADD PRIMARY KEY (`badge_type_id`),
  ADD UNIQUE KEY `uq_reward_code` (`reward_code`);

--
-- Indexes for table `diary_entries`
--
ALTER TABLE `diary_entries`
  ADD PRIMARY KEY (`diary_id`),
  ADD KEY `fk_diary_user` (`user_id`);

--
-- Indexes for table `diary_monthly_reflections`
--
ALTER TABLE `diary_monthly_reflections`
  ADD PRIMARY KEY (`reflection_id`),
  ADD UNIQUE KEY `unique_diary_reflection_user_month` (`user_id`,`reflection_month`);

--
-- Indexes for table `exercise`
--
ALTER TABLE `exercise`
  ADD PRIMARY KEY (`exercise_id`),
  ADD KEY `fk_exercise_user` (`user_id`);

--
-- Indexes for table `fitness_profile`
--
ALTER TABLE `fitness_profile`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `habits`
--
ALTER TABLE `habits`
  ADD PRIMARY KEY (`habit_id`),
  ADD KEY `fk_habits_category` (`category_id`),
  ADD KEY `idx_habits_user_status` (`user_id`,`status`);

--
-- Indexes for table `habit_badges`
--
ALTER TABLE `habit_badges`
  ADD PRIMARY KEY (`badge_id`),
  ADD KEY `fk_habit_badges_habit` (`habit_id`),
  ADD KEY `fk_habit_badges_type` (`badge_type_id`),
  ADD KEY `idx_habit_badges_user` (`user_id`);

--
-- Indexes for table `habit_categories`
--
ALTER TABLE `habit_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `uq_category_name` (`category_name`);

--
-- Indexes for table `habit_logs`
--
ALTER TABLE `habit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD UNIQUE KEY `uq_habit_daily_checkin` (`habit_id`,`log_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `achievements`
--
ALTER TABLE `achievements`
  MODIFY `achievement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `badge_types`
--
ALTER TABLE `badge_types`
  MODIFY `badge_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `diary_entries`
--
ALTER TABLE `diary_entries`
  MODIFY `diary_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `diary_monthly_reflections`
--
ALTER TABLE `diary_monthly_reflections`
  MODIFY `reflection_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `exercise`
--
ALTER TABLE `exercise`
  MODIFY `exercise_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `fitness_profile`
--
ALTER TABLE `fitness_profile`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `habits`
--
ALTER TABLE `habits`
  MODIFY `habit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `habit_badges`
--
ALTER TABLE `habit_badges`
  MODIFY `badge_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `habit_categories`
--
ALTER TABLE `habit_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `habit_logs`
--
ALTER TABLE `habit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `achievements`
--
ALTER TABLE `achievements`
  ADD CONSTRAINT `fk_achievement_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `diary_entries`
--
ALTER TABLE `diary_entries`
  ADD CONSTRAINT `fk_diary_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `diary_monthly_reflections`
--
ALTER TABLE `diary_monthly_reflections`
  ADD CONSTRAINT `fk_diary_reflection_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `exercise`
--
ALTER TABLE `exercise`
  ADD CONSTRAINT `fk_exercise_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `fitness_profile`
--
ALTER TABLE `fitness_profile`
  ADD CONSTRAINT `fk_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `habits`
--
ALTER TABLE `habits`
  ADD CONSTRAINT `fk_habits_category` FOREIGN KEY (`category_id`) REFERENCES `habit_categories` (`category_id`),
  ADD CONSTRAINT `fk_habits_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `habit_badges`
--
ALTER TABLE `habit_badges`
  ADD CONSTRAINT `fk_habit_badges_habit` FOREIGN KEY (`habit_id`) REFERENCES `habits` (`habit_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_habit_badges_type` FOREIGN KEY (`badge_type_id`) REFERENCES `badge_types` (`badge_type_id`),
  ADD CONSTRAINT `fk_habit_badges_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `habit_logs`
--
ALTER TABLE `habit_logs`
  ADD CONSTRAINT `fk_habit_logs_habit` FOREIGN KEY (`habit_id`) REFERENCES `habits` (`habit_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
