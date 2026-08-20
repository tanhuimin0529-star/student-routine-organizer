USE student_routine_organizer;

CREATE TABLE IF NOT EXISTS habit_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    category_icon VARCHAR(50) DEFAULT NULL,

    UNIQUE KEY uq_category_name (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS habits (
    habit_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,

    habit_name VARCHAR(100) NOT NULL,
    habit_description TEXT DEFAULT NULL,

    target_frequency INT UNSIGNED NOT NULL,
    frequency_type ENUM('Daily', 'Weekly', 'Monthly') NOT NULL,

    start_date DATE NOT NULL,
    status ENUM('Active', 'Paused', 'Completed', 'Archived')
        NOT NULL DEFAULT 'Active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_habits_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_habits_category
        FOREIGN KEY (category_id) REFERENCES habit_categories(category_id)
        ON DELETE RESTRICT,

    INDEX idx_habits_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS habit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    habit_id INT NOT NULL,

    log_date DATE NOT NULL,
    log_time TIME NOT NULL,
    completed BOOLEAN NOT NULL DEFAULT TRUE,
    log_note VARCHAR(255) DEFAULT NULL,

    CONSTRAINT fk_habit_logs_habit
        FOREIGN KEY (habit_id) REFERENCES habits(habit_id)
        ON DELETE CASCADE,

    CONSTRAINT uq_habit_daily_checkin
        UNIQUE (habit_id, log_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS badge_types (
    badge_type_id INT AUTO_INCREMENT PRIMARY KEY,

    reward_code VARCHAR(50) NOT NULL,
    reward_name VARCHAR(100) NOT NULL,
    reward_description VARCHAR(255) NOT NULL,

    requirement INT UNSIGNED NOT NULL,
    tree_tier TINYINT UNSIGNED NOT NULL DEFAULT 1,

    reward_type ENUM('Badge', 'Decoration') NOT NULL,
    slot ENUM('Hat', 'Glasses', 'Clothes', 'Shoes', 'Background')
        DEFAULT NULL,

    reward_asset VARCHAR(255) DEFAULT NULL,

    UNIQUE KEY uq_reward_code (reward_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS habit_badges (
    badge_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,
    habit_id INT DEFAULT NULL,
    badge_type_id INT NOT NULL,

    earned_date DATE NOT NULL,
    is_equipped BOOLEAN NOT NULL DEFAULT FALSE,

    CONSTRAINT fk_habit_badges_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_habit_badges_habit
        FOREIGN KEY (habit_id) REFERENCES habits(habit_id)
        ON DELETE SET NULL,

    CONSTRAINT fk_habit_badges_type
        FOREIGN KEY (badge_type_id) REFERENCES badge_types(badge_type_id)
        ON DELETE RESTRICT,

    INDEX idx_habit_badges_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO habit_categories (category_name, category_icon) VALUES
    ('Health', 'heart'),
    ('Study', 'book'),
    ('Fitness', 'dumbbell'),
    ('Lifestyle', 'leaf');

INSERT IGNORE INTO badge_types (
    reward_code,
    reward_name,
    reward_description,
    requirement,
    tree_tier,
    reward_type,
    slot,
    reward_asset
) VALUES
(
    'streak_3',
    '3-Day Streak',
    'Complete the same habit for 3 consecutive days.',
    3,
    1,
    'Badge',
    NULL,
    '🏅'
),
(
    'streak_7',
    '7-Day Streak',
    'Complete the same habit for 7 consecutive days.',
    7,
    2,
    'Badge',
    NULL,
    '🏆'
),
(
    'checkins_10',
    'Ten Check-ins',
    'Complete any 10 habit check-ins.',
    10,
    2,
    'Badge',
    NULL,
    '⭐'
);
