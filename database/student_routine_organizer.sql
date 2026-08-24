-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 24, 2026 at 09:03 AM
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
(3, 1, '1000 Calories', '💪', 'Burned over 1000 total calories!', '2026-08-19 06:12:14'),
(4, 4, 'First Workout', '🎯', 'Logged your very first exercise!', '2026-08-23 16:25:13');

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

INSERT INTO `diary_entries` (`diary_id`, `user_id`, `title`, `content`, `mood`, `weather`, `entry_date`, `created_at`, `updated_at`, `is_favorite`) VALUES
(23, 4, 'A Productive Day', '<!--DIARY_RICH_TEXT_V1-->Today was a really productive day for me. 👍<p>I managed to finish most of my assignments before dinner.</p><p>I also reviewed some lecture notes that I had been delaying.</p><p>It felt good to finally clear several tasks from my list.</p><p>I hope I can maintain this momentum for the rest of the week.</p><figure data-diary-object=\"drawing\" data-diary-x=\"80.7\" data-diary-y=\"74.1\" data-diary-width=\"35\" data-diary-rotation=\"0\"><img src=\"/student-routine-organizer/uploads/diary/user_4/06bb204e9c49d06aa00e9a05f57a345f.png\" alt=\"Journal drawing\"></figure>', 'Happy', 'Sunny', '2026-08-17', '2026-08-23 17:43:17', '2026-08-23 17:43:17', 1),
(24, 4, 'Feeling a Little Tired', '<!--DIARY_RICH_TEXT_V1-->I felt quite tired today because I did not sleep enough last night.<p>It was difficult to concentrate during the morning lecture.</p><p>After class, I went back and rested for a while.</p><p>I still managed to complete a small part of my work in the evening.</p><p><span style=\"background-color: #dce9ce\">Tonight, I want to sleep earlier and take better care of myself.</span></p>', 'Neutral', 'Cloudy', '2026-08-18', '2026-08-23 17:44:16', '2026-08-23 17:47:17', 0),
(25, 4, 'Rainy Study Day', '<!--DIARY_RICH_TEXT_V1--><p style=\"text-align: left\">It rained for most of the day, so I stayed indoors after class.</p><p style=\"text-align: left\">The weather made the room feel quiet and comfortable.</p><p style=\"text-align: left\">I made a cup of tea and spent some time revising my notes.</p><p style=\"text-align: left\">I also organised the files for my group assignment.</p><p><p style=\"text-align: left\">Overall, it was a peaceful and simple day.</p><figure data-diary-size=\"medium\" data-diary-align=\"center\" data-diary-wrap=\"right\"><img src=\"/student-routine-organizer/uploads/diary/user_4/57d4841e1f9b846aedc38a7c12c6eb0a.webp\" alt=\"rain\"></figure><br></p>', 'Calm', 'Rainy', '2026-08-19', '2026-08-23 17:46:47', '2026-08-23 17:46:47', 1),
(26, 4, 'Group Project Progress', '<!--DIARY_RICH_TEXT_V1--><p style=\"text-align: right\"><span style=\"font-family: Verdana\">My group had a productive discussion about our assignment today.</span></p><p style=\"text-align: right\"><span style=\"font-family: Verdana\">We divided the remaining tasks and checked each member\'s progress.</span></p><p style=\"text-align: right\"><span style=\"font-family: Verdana\">There were a few different opinions, but we managed to reach an agreement.</span></p><p style=\"text-align: right\"><span style=\"font-family: Verdana\">I completed my part earlier than I expected.</span></p><p style=\"text-align: right\"><span style=\"font-family: Verdana\">I feel more <b>confident</b> about finishing the project on time.</span></p><figure data-diary-object=\"drawing\" data-diary-x=\"17.5\" data-diary-y=\"75.6\" data-diary-width=\"35\" data-diary-rotation=\"0\"><img src=\"/student-routine-organizer/uploads/diary/user_4/a9ced036dfa221cee66b34808edc5439.png\" alt=\"Journal drawing\"></figure>', 'Happy', 'Windy', '2026-08-20', '2026-08-23 17:49:08', '2026-08-23 17:49:08', 0),
(27, 4, 'A Stressful Afternoon', '<!--DIARY_RICH_TEXT_V1--><ul><li>This afternoon was more stressful than I expected.</li><li>I realised that I still had several tasks to complete before the deadline.</li><li>For a while, I felt overwhelmed and did not know what to do first.</li><li>I made a small task list and started working through it one by one.</li><li>I am still worried, but at least the situation feels more manageable now.</li></ul><figure data-diary-object=\"drawing\" data-diary-x=\"81.4\" data-diary-y=\"73.7\" data-diary-width=\"35\" data-diary-rotation=\"0\"><img src=\"/student-routine-organizer/uploads/diary/user_4/ec2e54abee1cbe680fe744145f2edd88.png\" alt=\"Journal drawing\"></figure>', 'Stressed', NULL, '2026-08-21', '2026-08-23 17:50:51', '2026-08-23 17:50:51', 0),
(30, 4, 'mall Achievement', '<!--DIARY_RICH_TEXT_V1--><span style=\"font-family: Courier New\"><b>I finally solved a problem that had been confusing me for several days.</b></span><p><span style=\"font-family: Courier New\"><b>I spent a lot of time checking my work and trying different solutions.</b></span></p><p><span style=\"font-family: Courier New\"><b>When everything finally worked, I felt very relieved.</b></span></p><p><span style=\"font-family: Courier New\"><b>It reminded me that being patient is important when learning something new.</b></span></p><p><span style=\"font-family: Courier New\"><b>Today felt like a small but meaningful achievement.</b></span></p>', 'Happy', 'Sunny', '2026-08-16', '2026-08-23 17:55:58', '2026-08-23 17:55:58', 0),
(31, 4, 'Funny day', '<!--DIARY_RICH_TEXT_V1-->hsjfbsjbfjfbs', 'Happy', 'Sunny', '2026-08-24', '2026-08-23 18:23:52', '2026-08-23 18:23:52', 0),
(32, 4, 'Funny day', '<!--DIARY_RICH_TEXT_V1-->hsfgbsjkfbsff<figure data-diary-size=\"medium\" data-diary-align=\"center\" data-diary-wrap=\"none\"><img src=\"/student-routine-organizer/uploads/diary/user_4/61212ab9aca457cf8d46a20a5dca17f4.webp\" alt=\"Journal image\"><figcaption>rainy</figcaption></figure><figure data-diary-size=\"medium\" data-diary-align=\"center\" data-diary-wrap=\"right\"><img src=\"/student-routine-organizer/uploads/diary/user_4/a8c92d3dae792d8b7993d57f48c22951.webp\" alt=\"Journal image\"></figure><br><figure data-diary-object=\"drawing\" data-diary-x=\"82.5\" data-diary-y=\"15.9\" data-diary-width=\"35\" data-diary-rotation=\"0\"><img src=\"/student-routine-organizer/uploads/diary/user_4/aad2a9dec19c49fb05bcd685b4668395.png\" alt=\"Journal drawing\"></figure>', 'Happy', 'Sunny', '2026-08-24', '2026-08-23 18:38:06', '2026-08-23 19:20:17', 0),
(33, 4, 'retge', '<!--DIARY_RICH_TEXT_V1-->reger', 'Neutral', 'Cloudy', '2026-08-24', '2026-08-23 18:53:16', '2026-08-23 18:53:16', 0);

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
(1, 4, '2026-08-01', 'jbfejhr', '2026-08-22 04:23:23', '2026-08-23 19:12:26'),
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
(41, 2, 'Swimming', 35, 280, '2026-08-14', 'Pool session', '2026-08-19 06:12:14', '2026-08-19 06:12:14'),
(42, 4, 'Cycling', 4, 5, '2026-08-24', '', '2026-08-23 16:25:10', '2026-08-23 16:25:10'),
(43, 4, 'Swimming', 6, 6, '2026-08-24', '', '2026-08-23 19:22:52', '2026-08-23 19:22:52');

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
(2, 4, 170.0, 65.0, 500, 10000, 0, 0, 0.0, '2026-08-24', '2026-08-23 16:25:13');

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

--
-- Dumping data for table `habits`
--

INSERT INTO `habits` (`habit_id`, `user_id`, `category_id`, `habit_name`, `habit_description`, `target_frequency`, `frequency_type`, `start_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, 4, 'rea one book', '', 1, 'Daily', '2026-08-24', 'Active', '2026-08-23 19:23:17', '2026-08-23 19:23:17'),
(2, 4, 1, 'rea one book', 'rtert', 1, 'Daily', '2026-08-24', 'Active', '2026-08-23 19:23:44', '2026-08-23 19:23:44');

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

--
-- Dumping data for table `habit_logs`
--

INSERT INTO `habit_logs` (`log_id`, `habit_id`, `log_date`, `log_time`, `completed`, `log_note`) VALUES
(1, 2, '2026-08-24', '03:23:46', 1, NULL),
(2, 1, '2026-08-24', '03:23:47', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `money_budgets`
--

CREATE TABLE `money_budgets` (
  `budget_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `monthly_limit` decimal(10,2) NOT NULL,
  `budget_month` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `money_transactions`
--

CREATE TABLE `money_transactions` (
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_type` enum('Income','Expense') NOT NULL,
  `category` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `money_transactions`
--

INSERT INTO `money_transactions` (`transaction_id`, `user_id`, `transaction_type`, `category`, `amount`, `transaction_date`, `description`, `created_at`, `updated_at`) VALUES
(1, 4, 'Income', 'Shopping', 5.00, '2026-08-24', '', '2026-08-23 19:24:05', '2026-08-23 19:24:05');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `reset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`reset_id`, `user_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES
(3, 4, '25c1bc5a00e61f9879b78ff7cb6393466c34aff447a163cbab582fd31c512adf', '2026-08-23 19:10:03', '2026-08-23 18:42:46', '2026-08-23 10:40:03'),
(4, 4, '452ff731982cadb2282cad42abd62e46b26ef936afa97bdc9f9fa49e6291e918', '2026-08-23 19:12:46', '2026-08-23 18:52:48', '2026-08-23 10:42:46'),
(6, 4, '5cdd61dc90efb3537d25bd04df221f3a56b7e5c5cb81db6e2cb03e7c8a255a35', '2026-08-23 19:26:50', '2026-08-23 18:57:03', '2026-08-23 10:56:50'),
(7, 4, '81f3af745d0d24faad99a59b5509a00340dd2608b963fb553dd5eedeeca49ddf', '2026-08-23 19:27:03', '2026-08-23 19:06:01', '2026-08-23 10:57:03'),
(8, 4, '46700e71fa1d57d0b364710004752d0ca54ab2aa517a2ccb8a46da09f64ce36f', '2026-08-23 19:36:01', '2026-08-23 19:06:09', '2026-08-23 11:06:01'),
(9, 4, 'd36c608483c297269de4ebe4bf384a2a3aed5c2cbb14a1b00538638b18388edc', '2026-08-23 19:36:09', '2026-08-23 19:06:30', '2026-08-23 11:06:09'),
(10, 4, '58645495f465b6c1fdedbed29a1e249a09e181437e4df3e1b3f41dee0eb11f22', '2026-08-23 19:36:30', '2026-08-23 19:06:44', '2026-08-23 11:06:30'),
(11, 4, 'd6b081f55256d0b86e2a5c55fd9177195fa1c8dfe53cd23da27e91f094a55813', '2026-08-23 19:36:44', '2026-08-23 19:06:49', '2026-08-23 11:06:44'),
(12, 4, '78bb1c939dbfc27555d47bf77de624e62f1576e9c04e7d5bc88a41b5a273b93b', '2026-08-23 19:36:49', '2026-08-24 00:15:40', '2026-08-23 11:06:49'),
(15, 4, 'cd43edb521b1b3b4acf03c8e530a570c3dae7bc9a81a6acc62e660fefb84b589', '2026-08-24 00:45:40', '2026-08-24 00:41:23', '2026-08-23 16:15:40'),
(16, 4, '013140ab00047003a1a879c415d8fa716963aec72c279e639145098deb4f5ee7', '2026-08-24 01:11:23', '2026-08-24 00:51:28', '2026-08-23 16:41:23'),
(17, 4, '596e9adf7da8519e8e1124a3ecaddbaa1eb367164eb0e941edbea03323f033b1', '2026-08-24 01:21:28', '2026-08-24 01:08:51', '2026-08-23 16:51:28'),
(18, 4, '0aef688526b1c1539e2ea1a00f9fd162811944507cdc287599962912d8a34c5b', '2026-08-24 01:38:51', '2026-08-24 01:09:33', '2026-08-23 17:08:51'),
(19, 4, 'b890b81fa346e9ba10cf851cd90faf46bb0d01357e2bf6edfcafb4872f893e09', '2026-08-24 01:40:05', '2026-08-24 13:21:23', '2026-08-23 17:10:05'),
(20, 4, 'b28bb4a3e4125d7b314210c59a37af7dec73cea0db47f6403de471adae3ae5a5', '2026-08-24 13:51:23', '2026-08-24 13:22:19', '2026-08-24 05:21:23');

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
(4, 'Tan Hui Min', 'huimin20@1utar.my', '$2y$10$HZGh5eoGy8hi6Ow/Mzv3g.PDO22M2ICvWpl1pb.GdDNFX/OLKROTW', 'student', '2026-08-19 06:30:54'),
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
-- Indexes for table `money_budgets`
--
ALTER TABLE `money_budgets`
  ADD PRIMARY KEY (`budget_id`),
  ADD UNIQUE KEY `unique_user_category_month` (`user_id`,`category`,`budget_month`);

--
-- Indexes for table `money_transactions`
--
ALTER TABLE `money_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `idx_money_user_date` (`user_id`,`transaction_date`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`reset_id`),
  ADD UNIQUE KEY `uq_password_reset_token_hash` (`token_hash`),
  ADD KEY `idx_password_reset_user` (`user_id`),
  ADD KEY `idx_password_reset_expiry` (`expires_at`);

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
  MODIFY `achievement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `badge_types`
--
ALTER TABLE `badge_types`
  MODIFY `badge_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `diary_entries`
--
ALTER TABLE `diary_entries`
  MODIFY `diary_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `diary_monthly_reflections`
--
ALTER TABLE `diary_monthly_reflections`
  MODIFY `reflection_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `exercise`
--
ALTER TABLE `exercise`
  MODIFY `exercise_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `fitness_profile`
--
ALTER TABLE `fitness_profile`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `habits`
--
ALTER TABLE `habits`
  MODIFY `habit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `money_budgets`
--
ALTER TABLE `money_budgets`
  MODIFY `budget_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `money_transactions`
--
ALTER TABLE `money_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

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

--
-- Constraints for table `money_budgets`
--
ALTER TABLE `money_budgets`
  ADD CONSTRAINT `fk_money_budget_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `money_transactions`
--
ALTER TABLE `money_transactions`
  ADD CONSTRAINT `fk_money_transaction_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
