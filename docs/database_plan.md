# Section 6 — Database Planning

---

## Database Name
`student_routine_organizer`

---

## Tables Overview

| Table | Module | Description |
|-------|--------|-------------|
| `users` | Auth/Shared | All registered users (students and admins) |
| `exercise_logs` | Exercise | Individual workout session records |
| `diary_entries` | Diary | Individual journal entries |
| `money_records` | Money | Individual financial transactions |
| `habits` | Habit | Habit definitions |
| `habit_logs` | Habit | Daily habit check-in records |

---

## Table Definitions

---

### Table: `users`

**Purpose:** Central user table. Every piece of data in the system is associated with a user via a foreign key relationship. This is the parent table for all module tables.

| Column | Data Type | Constraints | Description |
|--------|-----------|-------------|-------------|
| `user_id` | INT | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique identifier for each user |
| `full_name` | VARCHAR(100) | NOT NULL | User's full name |
| `email` | VARCHAR(150) | NOT NULL, UNIQUE | Login email address |
| `password_hash` | VARCHAR(255) | NOT NULL | Bcrypt-hashed password |
| `role` | ENUM('student','admin') | NOT NULL, DEFAULT 'student' | User role |
| `is_active` | TINYINT(1) | NOT NULL, DEFAULT 1 | Account active status (1=active, 0=deactivated) |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Account creation timestamp |
| `updated_at` | DATETIME | DEFAULT NULL, ON UPDATE CURRENT_TIMESTAMP | Last profile update timestamp |

**Primary Key:** `user_id`
**Unique Constraint:** `email`

---

### Table: `exercise_logs`

**Purpose:** Stores each individual workout session logged by a student.

| Column | Data Type | Constraints | Description |
|--------|-----------|-------------|-------------|
| `log_id` | INT | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique identifier for exercise log |
| `user_id` | INT | NOT NULL, FOREIGN KEY → users(user_id) | Owning student |
| `exercise_type` | VARCHAR(100) | NOT NULL | Type of exercise (Running, Gym, etc.) |
| `duration_minutes` | INT | NOT NULL | Duration in minutes |
| `intensity` | ENUM('Low','Medium','High') | NOT NULL | Workout intensity level |
| `exercise_date` | DATE | NOT NULL | Date of the exercise |
| `calories_burned` | INT | DEFAULT NULL | Optional calorie estimate |
| `notes` | TEXT | DEFAULT NULL | Optional notes |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| `updated_at` | DATETIME | DEFAULT NULL, ON UPDATE CURRENT_TIMESTAMP | Last update timestamp |

**Primary Key:** `log_id`
**Foreign Key:** `user_id` → `users(user_id)` ON DELETE CASCADE

---

### Table: `diary_entries`

**Purpose:** Stores each personal journal entry written by a student.

| Column | Data Type | Constraints | Description |
|--------|-----------|-------------|-------------|
| `entry_id` | INT | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique identifier for diary entry |
| `user_id` | INT | NOT NULL, FOREIGN KEY → users(user_id) | Owning student |
| `title` | VARCHAR(150) | NOT NULL | Entry title or headline |
| `content` | TEXT | NOT NULL | Full journal entry body |
| `mood` | ENUM('Happy','Sad','Stressed','Calm','Excited','Neutral') | NOT NULL | Mood tag |
| `entry_date` | DATE | NOT NULL | Date of the journal entry |
| `is_private` | TINYINT(1) | NOT NULL, DEFAULT 1 | Privacy flag |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| `updated_at` | DATETIME | DEFAULT NULL, ON UPDATE CURRENT_TIMESTAMP | Last update timestamp |

**Primary Key:** `entry_id`
**Foreign Key:** `user_id` → `users(user_id)` ON DELETE CASCADE

---

### Table: `money_records`

**Purpose:** Stores each financial transaction (income or expense) recorded by a student.

| Column | Data Type | Constraints | Description |
|--------|-----------|-------------|-------------|
| `record_id` | INT | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique identifier for transaction |
| `user_id` | INT | NOT NULL, FOREIGN KEY → users(user_id) | Owning student |
| `transaction_type` | ENUM('Income','Expense') | NOT NULL | Type of transaction |
| `amount` | DECIMAL(10,2) | NOT NULL | Transaction amount |
| `category` | VARCHAR(100) | NOT NULL | E.g., Food, Transport, Salary |
| `description` | VARCHAR(200) | NOT NULL | Short description |
| `transaction_date` | DATE | NOT NULL | Date of the transaction |
| `notes` | TEXT | DEFAULT NULL | Optional additional notes |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| `updated_at` | DATETIME | DEFAULT NULL, ON UPDATE CURRENT_TIMESTAMP | Last update timestamp |

**Primary Key:** `record_id`
**Foreign Key:** `user_id` → `users(user_id)` ON DELETE CASCADE

---

### Table: `habits`

**Purpose:** Stores the definition of each personal habit created by a student. A student may have many habits. Each habit is a parent to multiple daily check-in logs.

| Column | Data Type | Constraints | Description |
|--------|-----------|-------------|-------------|
| `habit_id` | INT | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique identifier for the habit |
| `user_id` | INT | NOT NULL, FOREIGN KEY → users(user_id) | Owning student |
| `habit_name` | VARCHAR(150) | NOT NULL | Name of the habit |
| `description` | TEXT | DEFAULT NULL | Optional description |
| `frequency` | ENUM('Daily','Weekly','Custom') | NOT NULL, DEFAULT 'Daily' | Tracking frequency |
| `start_date` | DATE | NOT NULL | When tracking begins |
| `target_end_date` | DATE | DEFAULT NULL | Optional goal end date |
| `is_active` | TINYINT(1) | NOT NULL, DEFAULT 1 | Active/Archived status |
| `current_streak` | INT | NOT NULL, DEFAULT 0 | Current consecutive completion count |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| `updated_at` | DATETIME | DEFAULT NULL, ON UPDATE CURRENT_TIMESTAMP | Last update timestamp |

**Primary Key:** `habit_id`
**Foreign Key:** `user_id` → `users(user_id)` ON DELETE CASCADE

---

### Table: `habit_logs`

**Purpose:** Stores each daily check-in event for a specific habit. Used to calculate streaks and completion history.

| Column | Data Type | Constraints | Description |
|--------|-----------|-------------|-------------|
| `log_id` | INT | PRIMARY KEY, AUTO_INCREMENT, NOT NULL | Unique identifier |
| `habit_id` | INT | NOT NULL, FOREIGN KEY → habits(habit_id) | Parent habit |
| `user_id` | INT | NOT NULL, FOREIGN KEY → users(user_id) | Owning student (denormalized for easy querying) |
| `checkin_date` | DATE | NOT NULL | Date of the check-in |
| `notes` | VARCHAR(255) | DEFAULT NULL | Optional check-in notes |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Log creation timestamp |

**Primary Key:** `log_id`
**Unique Constraint:** `(habit_id, checkin_date)` — Prevents duplicate check-ins per habit per day
**Foreign Keys:**
- `habit_id` → `habits(habit_id)` ON DELETE CASCADE
- `user_id` → `users(user_id)` ON DELETE CASCADE

---

## Relationships

| Relationship | Type | Detail |
|-------------|------|--------|
| `users` → `exercise_logs` | One-to-Many | One student can have many exercise logs |
| `users` → `diary_entries` | One-to-Many | One student can have many diary entries |
| `users` → `money_records` | One-to-Many | One student can have many money records |
| `users` → `habits` | One-to-Many | One student can have many habits |
| `habits` → `habit_logs` | One-to-Many | One habit can have many daily check-ins |
| `users` → `habit_logs` | One-to-Many | One student can have many habit log entries |

---

## Normalization

The database is designed to comply with **Third Normal Form (3NF)**:

- **1NF (First Normal Form):** All tables contain atomic values. No repeating groups or multi-valued columns.
- **2NF (Second Normal Form):** All non-key attributes are fully dependent on the entire primary key. All tables use a single-column surrogate primary key (auto-increment integer), so partial dependency is not an issue.
- **3NF (Third Normal Form):** No transitive dependencies. All non-key columns depend directly on the primary key, not on other non-key columns. For example, user information is not duplicated into module tables — it is referenced via `user_id` foreign key.

### Deliberate Denormalization
- `habit_logs.user_id` is included as a denormalization for query performance. While the `user_id` can be derived via JOIN through `habits`, including it directly avoids an extra JOIN on high-frequency queries.

---

## How Modules Connect to Users

Every module table includes a `user_id` column that references `users(user_id)`. This ensures:

1. **Data Isolation** — A student can only query their own records by filtering on `user_id = $_SESSION['user_id']`.
2. **Cascading Deletes** — If an admin deletes a user account, all related records across all modules are automatically removed via `ON DELETE CASCADE`.
3. **Consistent Identity** — The `users` table is the single source of truth for identity across the whole system.

---

*Document prepared for: Student Routine Organizer — University Group Assignment*
