# Section 4 — Module Planning

---

## Module 1: Exercise Tracker

### Purpose
Allows students to log, view, update, and delete their physical exercise sessions. Helps students maintain awareness of their fitness habits and encourages consistency.

### Features
- Log a new workout session with type, duration, intensity, and notes.
- View a list of all personal exercise records sorted by date.
- Edit an existing exercise record.
- Delete an exercise record.
- Filter records by exercise type or date range.
- Display total exercise duration for the current week.

### CRUD Operations

| Operation | Description |
|-----------|-------------|
| Create | Student logs a new exercise session |
| Read | Student views their list of exercise logs |
| Update | Student edits a past exercise record |
| Delete | Student removes an exercise record |

### Input Fields

| Field Name | Type | Description |
|------------|------|-------------|
| `exercise_type` | Text / Dropdown | E.g., Running, Swimming, Gym, Cycling |
| `duration_minutes` | Integer | Duration in minutes |
| `intensity` | Dropdown | Low / Medium / High |
| `exercise_date` | Date | Date of the activity |
| `calories_burned` | Integer (optional) | Estimated calories burned |
| `notes` | Textarea (optional) | Additional comments |

### Validation Rules
- `exercise_type`: Required, must not be empty, max 100 characters.
- `duration_minutes`: Required, must be a positive integer between 1 and 1440.
- `intensity`: Required, must match allowed values (Low/Medium/High).
- `exercise_date`: Required, must be a valid date, cannot be in the future.
- `calories_burned`: Optional, must be a non-negative integer if provided.
- `notes`: Optional, max 500 characters.
- All inputs must be sanitized to prevent XSS.

### Possible Future Improvements
- Weekly or monthly exercise summary chart.
- Goal-setting feature (e.g., "Run 30 km this month").
- Export exercise log to CSV or PDF.
- Integration with a Habit Tracker (e.g., "Exercise daily" as a habit).
- Recommended workout playlists or tips.

---

## Module 2: Diary Journal

### Purpose
Provides students with a private, secure personal journal where they can record daily thoughts, feelings, and reflections. Supports mental well-being and self-awareness.

### Features
- Create a new diary entry with a title, content, mood tag, and date.
- View a list of all personal diary entries sorted by date.
- Read the full content of a single diary entry.
- Edit an existing diary entry.
- Delete a diary entry.
- Filter entries by mood tag or date.

### CRUD Operations

| Operation | Description |
|-----------|-------------|
| Create | Student writes a new diary entry |
| Read | Student views their entry list and individual entries |
| Update | Student edits a past entry |
| Delete | Student removes an entry |

### Input Fields

| Field Name | Type | Description |
|------------|------|-------------|
| `title` | Text | Short title or headline for the entry |
| `content` | Textarea | Full journal entry body |
| `mood` | Dropdown | E.g., Happy, Sad, Stressed, Calm, Excited, Neutral |
| `entry_date` | Date | Date of the journal entry |
| `is_private` | Checkbox / Boolean | Mark entry as private (default: true) |

### Validation Rules
- `title`: Required, max 150 characters.
- `content`: Required, max 5000 characters.
- `mood`: Required, must match allowed mood values.
- `entry_date`: Required, must be a valid date, cannot be in the future.
- `is_private`: Boolean, default to true if not provided.
- All inputs sanitized to prevent XSS and HTML injection.

### Possible Future Improvements
- Rich text editor (WYSIWYG) for formatting diary content.
- Mood trend chart over time.
- Search entries by keyword.
- Diary entry word count tracker.
- Ability to add image attachments.

---

## Module 3: Money Tracker

### Purpose
Enables students to log their income and expenses, categorize transactions, and view their financial balance. Promotes responsible financial management habits.

### Features
- Add a new income or expense transaction.
- View a list of all personal transactions sorted by date.
- Edit an existing transaction.
- Delete a transaction.
- Display a summary showing total income, total expenses, and current balance.
- Filter by category (expense type) or date range.

### CRUD Operations

| Operation | Description |
|-----------|-------------|
| Create | Student logs a new financial transaction |
| Read | Student views their transaction list and balance summary |
| Update | Student edits a transaction |
| Delete | Student removes a transaction |

### Input Fields

| Field Name | Type | Description |
|------------|------|-------------|
| `transaction_type` | Dropdown | Income / Expense |
| `amount` | Decimal | Transaction amount (positive number) |
| `category` | Dropdown / Text | E.g., Food, Transport, Education, Entertainment, Salary, Allowance |
| `description` | Text | Short description of the transaction |
| `transaction_date` | Date | Date of transaction |
| `notes` | Textarea (optional) | Additional details |

### Validation Rules
- `transaction_type`: Required, must be "Income" or "Expense".
- `amount`: Required, must be a positive decimal number, max 2 decimal places.
- `category`: Required, max 100 characters.
- `description`: Required, max 200 characters.
- `transaction_date`: Required, must be a valid date.
- `notes`: Optional, max 500 characters.
- All inputs sanitized.

### Possible Future Improvements
- Monthly budget goal setting with alerts.
- Pie chart breakdown of expense categories.
- Export transactions to CSV.
- Recurring transaction support (e.g., monthly allowance).
- Currency selector for international students.

---

## Module 4: Habit Tracker

### Purpose
Allows students to define personal habits and track their daily completion. Supports streak-based motivation to build and maintain positive routines.

### Features
- Create a new habit with name, description, frequency, and target date.
- View a list of all active personal habits.
- Mark a habit as completed for today (daily check-in).
- View streak count (consecutive days completed) for each habit.
- Edit habit details.
- Deactivate or delete a habit.

### CRUD Operations

| Operation | Description |
|-----------|-------------|
| Create | Student defines a new habit |
| Read | Student views their habits list and streak status |
| Update | Student edits habit details |
| Delete | Student removes/deactivates a habit |
| Check-in | Student marks today's habit as done (special operation) |

### Input Fields

**Habit Definition:**

| Field Name | Type | Description |
|------------|------|-------------|
| `habit_name` | Text | Name of the habit |
| `description` | Textarea (optional) | Habit description or motivation |
| `frequency` | Dropdown | Daily / Weekly / Custom |
| `start_date` | Date | When the habit tracking begins |
| `target_end_date` | Date (optional) | Goal end date |
| `is_active` | Boolean | Whether the habit is currently active |

**Daily Check-in:**

| Field Name | Type | Description |
|------------|------|-------------|
| `habit_id` | Integer | Reference to habit |
| `checkin_date` | Date | Date of check-in (today) |
| `notes` | Text (optional) | Notes about the check-in |

### Validation Rules
- `habit_name`: Required, max 150 characters.
- `description`: Optional, max 500 characters.
- `frequency`: Required, must be a valid option.
- `start_date`: Required, must be a valid date.
- `target_end_date`: Optional, must be after `start_date` if provided.
- `checkin_date`: Must equal today's date; duplicate check-ins for the same day must be rejected.
- All inputs sanitized.

### Possible Future Improvements
- Habit completion heat map (GitHub-style visualization).
- Notifications or reminders via browser alerts.
- Habit categorization (Health, Learning, Finance, etc.).
- Group habit challenges between students.
- Weekly habit summary report.

---

*Document prepared for: Student Routine Organizer — University Group Assignment*
