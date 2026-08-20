-- ===================================================================
-- Student Routine Organizer
-- Exercise Tracker Module - Database Script
-- Run this file inside phpMyAdmin (Import tab) or the SQL tab
-- ===================================================================

-- Create the database (only if it does not already exist)
CREATE DATABASE IF NOT EXISTS student_routine_organizer;
USE student_routine_organizer;

-- -------------------------------------------------------------
-- Users table
-- (This table is shared by the whole system. It is included here
--  so the Exercise Tracker can be tested on its own. If the other
--  group members already created it, this line will just be skipped
--  because of "IF NOT EXISTS".)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -------------------------------------------------------------
-- Exercise table (Exercise Tracker Module)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS exercise (
    exercise_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    activity_type    VARCHAR(50) NOT NULL,
    duration         INT NOT NULL COMMENT 'duration in minutes',
    calories_burned  INT NOT NULL,
    exercise_date    DATE NOT NULL,
    notes            VARCHAR(255) DEFAULT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_exercise_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
);

-- -------------------------------------------------------------
-- Fitness Profile table (stores user health/goal data)
-- Each user has one profile row. Created automatically on first
-- dashboard visit if it does not exist.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS fitness_profile (
    profile_id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL UNIQUE,
    height_cm         DECIMAL(5,1) DEFAULT 170.0,
    weight_kg         DECIMAL(5,1) DEFAULT 65.0,
    daily_calorie_goal INT DEFAULT 500,
    daily_step_goal   INT DEFAULT 10000,
    current_steps     INT DEFAULT 0,
    water_intake_ml   INT DEFAULT 0,
    sleep_hours       DECIMAL(3,1) DEFAULT 0.0,
    steps_date        DATE DEFAULT NULL COMMENT 'date the current_steps value belongs to',
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_profile_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
);

-- -------------------------------------------------------------
-- Achievements table (earned badges)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS achievements (
    achievement_id  INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    badge_name      VARCHAR(100) NOT NULL,
    badge_icon      VARCHAR(10) NOT NULL DEFAULT '🏅',
    description     VARCHAR(255) NOT NULL,
    earned_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_achievement_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_badge_per_user (user_id, badge_name)
);

-- -------------------------------------------------------------
-- Sample data so the module can be tested right away
-- IMPORTANT: the password column below is stored as plain text
-- ("password123") just so this file works with any INSERT tool.
-- The Login system uses password_hash()/password_verify(), which
-- needs a proper hash, not plain text. After importing this file,
-- run authentication/hash_seed_passwords.php ONCE in your browser
-- to convert these three accounts to real hashed passwords, then
-- delete that file. New accounts created through register.php are
-- always hashed correctly and do not need this step.
-- -------------------------------------------------------------
INSERT INTO users (name, email, password, role) VALUES
('Ali Hassan', 'ali@example.com', 'password123', 'student'),
('Siti Aminah', 'siti@example.com', 'password123', 'student'),
('Admin User', 'admin@example.com', 'password123', 'admin');

-- -------------------------------------------------------------
-- Sample fitness profile for user 1
-- -------------------------------------------------------------
INSERT INTO fitness_profile (user_id, height_cm, weight_kg, daily_calorie_goal, daily_step_goal, current_steps, water_intake_ml, sleep_hours, steps_date)
VALUES (1, 175.0, 72.5, 500, 10000, 6500, 1500, 7.5, CURDATE());

-- -------------------------------------------------------------
-- 35+ sample exercise records for user 1 spanning the last 30 days
-- Covers all activity types for realistic charts and statistics
-- -------------------------------------------------------------
INSERT INTO exercise (user_id, activity_type, duration, calories_burned, exercise_date, notes) VALUES
-- Today and recent days
(1, 'Jogging',    35, 280, CURDATE(), 'Morning run around campus'),
(1, 'Gym',        50, 380, CURDATE(), 'Upper body and core workout'),
(1, 'Yoga',       30, 120, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Evening stretching session'),
(1, 'Cycling',    45, 320, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Cycled to the library and back'),
(1, 'Swimming',   40, 350, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Freestyle laps at university pool'),
(1, 'Jogging',    30, 250, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Interval training at the track'),
(1, 'Badminton',  60, 420, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Doubles match with friends'),
(1, 'Gym',        65, 450, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'Leg day — squats and deadlifts'),
(1, 'Walking',    40, 150, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'Walk through the botanical garden'),
(1, 'Jogging',    25, 210, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Quick morning jog'),
(1, 'Cycling',    55, 380, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'Long ride along the riverside'),
(1, 'Yoga',       45, 140, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'Power yoga class'),
(1, 'Swimming',   50, 400, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'Butterfly and backstroke drills'),
(1, 'Gym',        55, 400, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'Push day — chest and shoulders'),
(1, 'Football',   90, 650, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'Friendly match at campus field'),
(1, 'Jogging',    40, 320, DATE_SUB(CURDATE(), INTERVAL 9 DAY), '5K run — personal best attempt'),
(1, 'Badminton',  45, 310, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Singles practice'),
(1, 'Cycling',    35, 260, DATE_SUB(CURDATE(), INTERVAL 11 DAY), 'Commute to campus'),
(1, 'Gym',        60, 420, DATE_SUB(CURDATE(), INTERVAL 12 DAY), 'Full body session'),
(1, 'Yoga',       35, 110, DATE_SUB(CURDATE(), INTERVAL 13 DAY), 'Morning meditation and flow'),
(1, 'Swimming',   45, 370, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 'Endurance swim — 2km'),
(1, 'Jogging',    30, 240, DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'Easy jog with music'),
(1, 'Walking',    50, 180, DATE_SUB(CURDATE(), INTERVAL 16 DAY), 'Hiking trail near campus'),
(1, 'Gym',        70, 500, DATE_SUB(CURDATE(), INTERVAL 17 DAY), 'Heavy lifting day'),
(1, 'Badminton',  50, 350, DATE_SUB(CURDATE(), INTERVAL 18 DAY), 'Tournament practice'),
(1, 'Cycling',    60, 420, DATE_SUB(CURDATE(), INTERVAL 19 DAY), 'Hill climbing route'),
(1, 'Football',   75, 550, DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'Inter-faculty game'),
(1, 'Jogging',    35, 270, DATE_SUB(CURDATE(), INTERVAL 21 DAY), 'Tempo run'),
(1, 'Swimming',   35, 300, DATE_SUB(CURDATE(), INTERVAL 22 DAY), 'Recovery swim'),
(1, 'Gym',        45, 350, DATE_SUB(CURDATE(), INTERVAL 23 DAY), 'Arms and abs'),
(1, 'Yoga',       40, 130, DATE_SUB(CURDATE(), INTERVAL 24 DAY), 'Flexibility focus'),
(1, 'Jogging',    45, 360, DATE_SUB(CURDATE(), INTERVAL 25 DAY), 'Long run — 8km'),
(1, 'Cycling',    40, 290, DATE_SUB(CURDATE(), INTERVAL 26 DAY), 'Casual evening ride'),
(1, 'Badminton',  55, 380, DATE_SUB(CURDATE(), INTERVAL 27 DAY), 'Club tournament'),
(1, 'Gym',        50, 370, DATE_SUB(CURDATE(), INTERVAL 28 DAY), 'Pull day — back and biceps'),
(1, 'Walking',    60, 200, DATE_SUB(CURDATE(), INTERVAL 29 DAY), 'Nature walk with friends'),
(1, 'Swimming',   40, 340, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'Mixed strokes session'),

-- Sample data for user 2 (Siti Aminah)
(2, 'Yoga',      40, 150, CURDATE(), 'Evening relaxation session'),
(2, 'Cycling',   50, 300, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Cycled to campus and back'),
(2, 'Jogging',   30, 230, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Morning jog'),
(2, 'Swimming',  35, 280, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Pool session');

-- Sample achievements for user 1
INSERT INTO achievements (user_id, badge_name, badge_icon, description) VALUES
(1, 'First Workout', '🎯', 'Logged your very first exercise!'),
(1, '7-Day Streak', '🔥', 'Exercised 7 days in a row!'),
(1, '1000 Calories', '💪', 'Burned over 1000 total calories!');
