# Section 10 — File Planning

---

## Overview

Every file in the project serves a defined responsibility within one of the three architectural tiers:

| Layer | Abbreviation | Description |
|-------|-------------|-------------|
| Presentation Layer | **PL** | Renders HTML to the browser; handles user interface |
| Business Logic Layer | **BL** | Processes requests, enforces rules, manages session/flow |
| Data Layer | **DL** | Directly accesses the database; returns data to BL |

---

## `includes/` Directory

| File | Purpose | Responsibility | Layer |
|------|---------|----------------|-------|
| `db_connect.php` | Establishes PDO connection to MySQL | Creates and returns a PDO connection object; used by all model files | DL |
| `session_start.php` | Safely starts or resumes the PHP session | Sets session security flags, starts session, sets `last_activity` | BL |
| `auth_guard.php` | Protects student-only pages | Checks session; redirects to login if unauthenticated | BL |
| `admin_guard.php` | Protects admin-only pages | Checks session + role; redirects non-admins to dashboard | BL |
| `header.php` | Shared navigation bar and HTML `<head>` section | Outputs the top navigation, linked CSS, and meta tags | PL |
| `footer.php` | Shared page footer | Outputs copyright info, linked JS, and closing HTML tags | PL |
| `functions.php` | Reusable utility functions | Input sanitization, flash message helpers, date formatters | BL |
| `flash_messages.php` | Displays and clears session-based flash messages | Outputs success/error messages set by handlers | PL/BL |

---

## `config/` Directory

| File | Purpose | Responsibility | Layer |
|------|---------|----------------|-------|
| `db_config.php` | Database credentials | Stores DB host, name, username, password constants; **not committed** | DL |
| `db_config.example.php` | Template for DB credentials | Safe placeholder version committed to repo | DL |
| `app_config.php` | Application-wide settings | Stores site name, session timeout duration, default role, etc. | BL |

---

## `authentication/` Directory

| File | Purpose | Responsibility | Layer |
|------|---------|----------------|-------|
| `register.php` | Registration form view | Renders the HTML registration form | PL |
| `register_handler.php` | Processes registration form | Validates input, hashes password, inserts user, redirects | BL |
| `login.php` | Login form view | Renders the HTML login form | PL |
| `login_handler.php` | Processes login form | Validates credentials, creates session, redirects by role | BL |
| `logout.php` | Handles logout | Destroys session, clears cookies, redirects | BL |
| `forgot_password.php` | Forgot password view (future) | Renders a password reset request form | PL |

---

## `dashboard/` Directory

| File | Purpose | Responsibility | Layer |
|------|---------|----------------|-------|
| `index.php` | Student dashboard view | Displays greeting, quick stats, and module navigation cards | PL + BL |
| `profile.php` | Student profile view and edit | Displays and allows editing of name, email, password | PL + BL |

---

## `modules/exercise/`

| File | Purpose | Responsibility | Layer |
|------|---------|----------------|-------|
| `index.php` | List all exercise records | Calls model, displays records in a table | PL + BL |
| `add.php` | Add exercise form view | Renders the add exercise HTML form | PL |
| `add_handler.php` | Processes add exercise form | Validates, sanitizes, calls model insert, redirects | BL |
| `edit.php` | Edit exercise form view | Loads record from model, renders pre-filled form | PL + BL |
| `edit_handler.php` | Processes edit exercise form | Validates, sanitizes, calls model update, redirects | BL |
| `delete_handler.php` | Processes delete request | Verifies ownership, calls model delete, redirects | BL |
| `exercise_model.php` | Database access for exercise | Functions: `getAllExerciseLogs()`, `getLogById()`, `insertLog()`, `updateLog()`, `deleteLog()` | DL |

---

## `modules/diary/`

| File | Purpose | Responsibility | Layer |
|------|---------|----------------|-------|
| `index.php` | List all diary entries | Calls model, displays entry list | PL + BL |
| `add.php` | Add diary entry form | Renders new entry form | PL |
| `add_handler.php` | Processes add entry form | Validates, calls model insert, redirects | BL |
| `view.php` | View single diary entry | Loads entry from model, renders full content | PL + BL |
| `edit.php` | Edit diary entry form | Pre-fills form with existing entry data | PL + BL |
| `edit_handler.php` | Processes edit entry form | Validates, calls model update, redirects | BL |
| `delete_handler.php` | Processes delete request | Verifies ownership, calls model delete, redirects | BL |
| `diary_model.php` | Database access for diary | Functions: `getAllEntries()`, `getEntryById()`, `insertEntry()`, `updateEntry()`, `deleteEntry()` | DL |

---

## `modules/money/`

| File | Purpose | Responsibility | Layer |
|------|---------|----------------|-------|
| `index.php` | List all transactions + balance | Calls model, calculates balance, displays list | PL + BL |
| `add.php` | Add transaction form | Renders the transaction form | PL |
| `add_handler.php` | Processes add transaction form | Validates, calls model insert, redirects | BL |
| `edit.php` | Edit transaction form | Pre-fills form with existing record | PL + BL |
| `edit_handler.php` | Processes edit transaction form | Validates, calls model update, redirects | BL |
| `delete_handler.php` | Processes delete request | Verifies ownership, calls model delete, redirects | BL |
| `money_model.php` | Database access for money | Functions: `getAllRecords()`, `getBalance()`, `insertRecord()`, `updateRecord()`, `deleteRecord()` | DL |

---

## `modules/habit/`

| File | Purpose | Responsibility | Layer |
|------|---------|----------------|-------|
| `index.php` | List all habits + today's status | Calls model, shows habits with streak and check-in button | PL + BL |
| `add.php` | Add habit definition form | Renders new habit form | PL |
| `add_handler.php` | Processes add habit form | Validates, calls model insert, redirects | BL |
| `edit.php` | Edit habit form | Pre-fills form with habit data | PL + BL |
| `edit_handler.php` | Processes edit habit form | Validates, calls model update, redirects | BL |
| `delete_handler.php` | Processes habit delete | Verifies ownership, calls model delete (cascades logs), redirects | BL |
| `checkin_handler.php` | Processes daily check-in (POST only) | Verifies no duplicate for today, inserts habit_log, updates streak | BL |
| `habit_model.php` | Database access for habits | Functions: `getAllHabits()`, `getHabitById()`, `insertHabit()`, `updateHabit()`, `deleteHabit()`, `logCheckin()`, `updateStreak()`, `alreadyCheckedIn()` | DL |

---

## `admin/` Directory

| File | Purpose | Responsibility | Layer |
|------|---------|----------------|-------|
| `index.php` | Admin dashboard view | Displays total users, recent activity stats | PL + BL |
| `users.php` | All users list | Retrieves and displays all users in the system | PL + BL |
| `user_detail.php` | Individual user detail | Retrieves and displays a selected user's profile | PL + BL |
| `deactivate_handler.php` | Deactivates a user account (POST only) | Sets `is_active = 0` for user, redirects | BL |
| `delete_user_handler.php` | Deletes a user account (POST only) | Executes user delete (cascades all related records), redirects | BL |
| `admin_model.php` | Database access for admin | Functions: `getAllUsers()`, `getUserById()`, `deactivateUser()`, `deleteUser()` | DL |

---

## `assets/` Static Files

| File | Purpose | Layer |
|------|---------|-------|
| `css/style.css` | Global layout and design styles | PL |
| `css/auth.css` | Auth page specific styles | PL |
| `css/dashboard.css` | Dashboard specific styles | PL |
| `css/{module}.css` | Per-module style files | PL |
| `js/main.js` | Global JS utilities | PL |
| `js/validation.js` | Shared client-side form validation | PL |
| `js/{module}.js` | Per-module JS files | PL |

---

*Document prepared for: Student Routine Organizer — University Group Assignment*
