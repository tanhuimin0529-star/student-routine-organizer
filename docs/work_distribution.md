# Section 11 — Four Member Work Distribution

---

## Distribution Principle

Work is divided into four equal streams:
- **Member 1** owns the Project Foundation and Authentication.
- **Member 2** owns the Exercise Tracker Module.
- **Member 3** owns the Diary Journal and Money Tracker Modules.
- **Member 4** owns the Habit Tracker and Admin Dashboard Modules.

All four members share responsibility for testing, integration, and documentation.

---

## Member 1 — Project Lead & Authentication Developer

### Responsibilities
- Set up the GitHub repository (folder structure, README, .gitignore, LICENSE).
- Configure XAMPP environment and database setup guide.
- Implement the `config/` directory files.
- Implement the entire `includes/` shared layer.
- Implement the complete `authentication/` module.
- Lead final integration and branch merging.
- Coordinate team meetings and maintain progress logs.

### Files Owned
**Config & Includes:**
- `config/db_config.example.php`
- `config/app_config.php`
- `includes/db_connect.php`
- `includes/session_start.php`
- `includes/auth_guard.php`
- `includes/admin_guard.php`
- `includes/header.php`
- `includes/footer.php`
- `includes/functions.php`
- `includes/flash_messages.php`

**Authentication:**
- `authentication/register.php`
- `authentication/register_handler.php`
- `authentication/login.php`
- `authentication/login_handler.php`
- `authentication/logout.php`

**Assets:**
- `assets/css/style.css` (global stylesheet — base design)
- `assets/css/auth.css`
- `assets/js/main.js`
- `assets/js/validation.js`

**Dashboard:**
- `dashboard/index.php`
- `dashboard/profile.php`
- `assets/css/dashboard.css`

### Module Owned
- Authentication System + Dashboard

### Documentation Owned
- `docs/project_overview.md`
- `docs/authentication_plan.md`
- `docs/git_branch_strategy.md`
- `docs/github_project_setup.md`
- `docs/meeting_notes/` (all meeting notes coordinator)
- `README.md`

---

## Member 2 — Exercise Tracker Developer

### Responsibilities
- Design and implement the complete Exercise Tracker module.
- Write the exercise model (Data Layer).
- Implement all CRUD handlers and views for exercise.
- Write exercise-specific CSS and JS.
- Write and run all test cases for the exercise module.

### Files Owned
- `modules/exercise/index.php`
- `modules/exercise/add.php`
- `modules/exercise/add_handler.php`
- `modules/exercise/edit.php`
- `modules/exercise/edit_handler.php`
- `modules/exercise/delete_handler.php`
- `modules/exercise/exercise_model.php`
- `assets/css/exercise.css`
- `assets/js/exercise.js`

### Module Owned
- Exercise Tracker

### Documentation Owned
- `docs/module_plan.md` (Exercise section)
- `docs/system_flow.md` (Exercise flows)
- `docs/testing_plan.md` (Exercise test cases)
- `docs/progress_logs/week2_progress.md`

---

## Member 3 — Diary Journal & Money Tracker Developer

### Responsibilities
- Design and implement the complete Diary Journal module.
- Design and implement the complete Money Tracker module.
- Write model files for both modules (Data Layer).
- Implement CRUD handlers and views for both modules.
- Write module-specific CSS and JS.
- Write and run test cases for both modules.

### Files Owned
**Diary Module:**
- `modules/diary/index.php`
- `modules/diary/add.php`
- `modules/diary/add_handler.php`
- `modules/diary/view.php`
- `modules/diary/edit.php`
- `modules/diary/edit_handler.php`
- `modules/diary/delete_handler.php`
- `modules/diary/diary_model.php`
- `assets/css/diary.css`
- `assets/js/diary.js`

**Money Module:**
- `modules/money/index.php`
- `modules/money/add.php`
- `modules/money/add_handler.php`
- `modules/money/edit.php`
- `modules/money/edit_handler.php`
- `modules/money/delete_handler.php`
- `modules/money/money_model.php`
- `assets/css/money.css`
- `assets/js/money.js`

### Modules Owned
- Diary Journal
- Money Tracker

### Documentation Owned
- `docs/module_plan.md` (Diary and Money sections)
- `docs/er_diagram.md` (primary author)
- `docs/database_plan.md` (primary author)
- `docs/system_flow.md` (Diary + Money flows)
- `docs/report_draft.md` (lead author)

---

## Member 4 — Habit Tracker & Admin Dashboard Developer

### Responsibilities
- Design and implement the complete Habit Tracker module (including check-in and streak logic).
- Design and implement the complete Admin Dashboard module.
- Write model files for both modules (Data Layer).
- Implement CRUD handlers/views and admin user management.
- Write module-specific CSS and JS.
- Write and run test cases for both modules.
- Prepare the final project submission package.

### Files Owned
**Habit Module:**
- `modules/habit/index.php`
- `modules/habit/add.php`
- `modules/habit/add_handler.php`
- `modules/habit/edit.php`
- `modules/habit/edit_handler.php`
- `modules/habit/delete_handler.php`
- `modules/habit/checkin_handler.php`
- `modules/habit/habit_model.php`
- `assets/css/habit.css`
- `assets/js/habit.js`

**Admin Module:**
- `admin/index.php`
- `admin/users.php`
- `admin/user_detail.php`
- `admin/deactivate_handler.php`
- `admin/delete_user_handler.php`
- `admin/admin_model.php`
- `assets/css/admin.css`
- `assets/js/admin.js`

### Modules Owned
- Habit Tracker
- Admin Dashboard

### Documentation Owned
- `docs/module_plan.md` (Habit section)
- `docs/system_flow.md` (Habit + Admin flows)
- `docs/testing_plan.md` (overall test results tracker)
- `docs/milestones.md`
- `docs/todo_list.md`
- `docs/final_report.md` (final formatting and submission)
- `docs/screenshots/` (all UI screenshots)

---

## Shared Integration Responsibilities

All four members are jointly responsible for:

| Responsibility | Who |
|----------------|-----|
| Database schema creation and testing | All (led by Member 1) |
| Merging feature branches into `develop` | All (one PR per feature) |
| Merging `develop` into `main` for release | Member 1 (with team approval) |
| Code review of each other's PRs | All (minimum 1 approver per PR) |
| Integration testing after all modules are merged | All |
| User Acceptance Testing (UAT) | All |
| Final report proofreading | All |

---

## Work Distribution Summary Table

| Responsibility | M1 | M2 | M3 | M4 |
|---------------|----|----|----|----|
| Repo Setup | ✅ | | | |
| Config & Includes | ✅ | | | |
| Authentication | ✅ | | | |
| Dashboard | ✅ | | | |
| Exercise Module | | ✅ | | |
| Diary Module | | | ✅ | |
| Money Module | | | ✅ | |
| Habit Module | | | | ✅ |
| Admin Dashboard | | | | ✅ |
| DB Schema | ✅ | | ✅ | |
| ER Diagram | | | ✅ | |
| Testing | ✅ | ✅ | ✅ | ✅ |
| Final Report | | | ✅ | ✅ |
| README | ✅ | | | |

---

*Document prepared for: Student Routine Organizer — University Group Assignment*
