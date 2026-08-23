-- =============================================================
-- Money Tracker Database Schema
-- Student Routine Organizer
-- =============================================================

-- -------------------------------------------------------------
-- Table: money_transactions
-- Stores each user's income and expense records
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS money_transactions (
    transaction_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    transaction_type ENUM('Income', 'Expense') NOT NULL,
    category VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_date DATE NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (transaction_id),
    KEY idx_money_user_date (user_id, transaction_date),

    CONSTRAINT fk_money_transaction_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- -------------------------------------------------------------
-- Table: money_budgets
-- Stores monthly spending budgets for each user
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS money_budgets (
    budget_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    category VARCHAR(50) NOT NULL,
    monthly_limit DECIMAL(10,2) NOT NULL,
    budget_month DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (budget_id),

    UNIQUE KEY unique_user_category_month
        (user_id, category, budget_month),

    CONSTRAINT fk_money_budget_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;