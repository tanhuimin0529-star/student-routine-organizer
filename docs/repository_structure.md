# Section 3 — GitHub Repository Structure

---

## Overview

The repository follows a clean, professional structure that enforces the Three-Tier Architecture and keeps each concern (authentication, modules, admin, assets, documentation) in its own directory. No implementation code appears here — this is the structural plan only.

---

## Root Directory

```
student-routine-organizer/
│
├── README.md
├── LICENSE
├── .gitignore
│
├── docs/
├── database/
├── config/
├── assets/
├── includes/
├── authentication/
├── dashboard/
├── modules/
│   ├── exercise/
│   ├── diary/
│   ├── money/
│   └── habit/
└── admin/
```

---

## Root-Level Files

| File | Purpose |
|------|---------|
| `README.md` | Project overview, setup instructions, team members, contribution guide |
| `LICENSE` | Open-source license declaration (MIT recommended for student projects) |
| `.gitignore` | Ignores sensitive/generated files: `config/db_config.php`, `/vendor/`, `*.log`, `.DS_Store`, `Thumbs.db` |

---

## `docs/` — Documentation

```
docs/
├── project_overview.md
├── development_roadmap.md
├── er_diagram.md
├── er_diagram.png            ← exported ER Diagram image
├── site_hierarchy.md
├── system_flow.md
├── database_plan.md
├── module_plan.md
├── authentication_plan.md
├── work_distribution.md
├── git_branch_strategy.md
├── testing_plan.md
├── milestones.md
├── todo_list.md
├── github_project_setup.md
├── meeting_notes/
│   ├── meeting_01.md
│   ├── meeting_02.md
│   └── meeting_03.md
├── progress_logs/
│   ├── week1_progress.md
│   ├── week2_progress.md
│   ├── week3_progress.md
│   └── week4_progress.md
├── screenshots/
│   └── (UI screenshots added during implementation)
├── report_draft.md
└── final_report.md
```

**Purpose:** All project planning documents, progress records, meeting notes, and final report drafts.

---

## `database/` — Database Files

```
database/
├── schema_description.md     ← Table-by-table description (no SQL)
├── schema.sql                ← SQL schema file (created during implementation)
├── seed_data.sql             ← Sample data for testing (created during implementation)
└── er_diagram.md             ← Duplicate/mirror of docs/er_diagram.md
```

**Purpose:** Centralized location for database schema, seed data, and ER Diagram artifacts.

---

## `config/` — Configuration

```
config/
├── db_config.php             ← Database credentials (listed in .gitignore)
├── db_config.example.php     ← Safe template with placeholder values (committed)
└── app_config.php            ← Global app settings (site name, session timeout, etc.)
```

**Purpose:** Central configuration files. The actual `db_config.php` is excluded from version control. The `.example` file is committed so team members know what credentials to supply locally.

---

## `assets/` — Static Resources

```
assets/
├── css/
│   ├── style.css             ← Global stylesheet
│   ├── auth.css              ← Styles for login/register pages
│   ├── dashboard.css         ← Dashboard-specific styles
│   ├── exercise.css
│   ├── diary.css
│   ├── money.css
│   ├── habit.css
│   └── admin.css
├── js/
│   ├── main.js               ← Global JavaScript utilities
│   ├── validation.js         ← Client-side form validation helpers
│   ├── exercise.js
│   ├── diary.js
│   ├── money.js
│   ├── habit.js
│   └── admin.js
└── images/
    ├── logo.png
    ├── default_avatar.png
    └── icons/
```

**Purpose:** All front-end static assets — stylesheets, scripts, images, and icons.

---

## `includes/` — Shared PHP Components

```
includes/
├── db_connect.php            ← Establishes PDO database connection
├── session_start.php         ← Starts session and sets security headers
├── auth_guard.php            ← Redirects unauthenticated users to login
├── admin_guard.php           ← Redirects non-admin users to dashboard
├── header.php                ← Shared HTML header (nav, meta tags)
├── footer.php                ← Shared HTML footer
├── functions.php             ← Reusable utility functions
└── flash_messages.php        ← Handles session-based success/error messages
```

**Purpose:** Shared PHP utilities and components reused across all modules. This is the core of the Business Logic Layer's shared functionality.

---

## `authentication/` — Auth Module

```
authentication/
├── register.php              ← Registration form (view)
├── register_handler.php      ← Registration processing (controller)
├── login.php                 ← Login form (view)
├── login_handler.php         ← Login processing (controller)
├── logout.php                ← Destroys session and redirects
└── forgot_password.php       ← (Future) Password reset page
```

**Purpose:** All authentication-related pages and handlers.

---

## `dashboard/` — Student Dashboard

```
dashboard/
├── index.php                 ← Main student dashboard view
└── profile.php               ← Student profile view and edit
```

**Purpose:** The central hub students see after logging in.

---

## `modules/` — Feature Modules

### `modules/exercise/`
```
modules/exercise/
├── index.php                 ← List all exercise records
├── add.php                   ← Add new exercise record (form view)
├── add_handler.php           ← Process add form (controller)
├── edit.php                  ← Edit exercise record (form view)
├── edit_handler.php          ← Process edit form (controller)
├── delete_handler.php        ← Delete exercise record (controller)
└── exercise_model.php        ← Data access functions for exercise (Data Layer)
```

### `modules/diary/`
```
modules/diary/
├── index.php
├── add.php
├── add_handler.php
├── edit.php
├── edit_handler.php
├── delete_handler.php
├── view.php                  ← View single diary entry
└── diary_model.php
```

### `modules/money/`
```
modules/money/
├── index.php
├── add.php
├── add_handler.php
├── edit.php
├── edit_handler.php
├── delete_handler.php
└── money_model.php
```

### `modules/habit/`
```
modules/habit/
├── index.php
├── add.php
├── add_handler.php
├── edit.php
├── edit_handler.php
├── delete_handler.php
├── checkin_handler.php       ← Marks a habit as done for today
└── habit_model.php
```

**Purpose:** Each module subdirectory is self-contained with its own views (Presentation Layer), handlers (Business Logic Layer), and model file (Data Layer).

---

## `admin/` — Admin Dashboard

```
admin/
├── index.php                 ← Admin dashboard overview
├── users.php                 ← List all registered users
├── user_detail.php           ← View individual user details
├── deactivate_handler.php    ← Deactivate user account
├── delete_user_handler.php   ← Delete user account
└── admin_model.php           ← Data access functions for admin operations
```

**Purpose:** Admin-only area for system and user management.

---

*Document prepared for: Student Routine Organizer — University Group Assignment*
