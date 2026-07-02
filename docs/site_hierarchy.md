# Section 8 — Site Hierarchy (Sitemap)

---

## Overview

The application has two separate navigation trees: one for **Student** users and one for **Admin** users. Both entry points begin at the Login page. Unauthenticated users are always redirected to Login.

---

## Complete Site Hierarchy

```
[PUBLIC]
├── Login                        → authentication/login.php
│   └── Forgot Password          → authentication/forgot_password.php (future)
└── Register                     → authentication/register.php

[STUDENT — Authenticated, Role: student]
└── Dashboard                    → dashboard/index.php
    ├── Profile                  → dashboard/profile.php
    │
    ├── Exercise Tracker         → modules/exercise/index.php
    │   ├── Add Record           → modules/exercise/add.php
    │   └── Edit Record          → modules/exercise/edit.php?id={log_id}
    │
    ├── Diary Journal            → modules/diary/index.php
    │   ├── Add Entry            → modules/diary/add.php
    │   ├── View Entry           → modules/diary/view.php?id={entry_id}
    │   └── Edit Entry           → modules/diary/edit.php?id={entry_id}
    │
    ├── Money Tracker            → modules/money/index.php
    │   ├── Add Transaction      → modules/money/add.php
    │   └── Edit Transaction     → modules/money/edit.php?id={record_id}
    │
    ├── Habit Tracker            → modules/habit/index.php
    │   ├── Add Habit            → modules/habit/add.php
    │   ├── Edit Habit           → modules/habit/edit.php?id={habit_id}
    │   └── Check In (Action)    → modules/habit/checkin_handler.php (POST only)
    │
    └── Logout                   → authentication/logout.php

[ADMIN — Authenticated, Role: admin]
└── Admin Dashboard              → admin/index.php
    ├── All Users                → admin/users.php
    │   └── User Detail          → admin/user_detail.php?id={user_id}
    │       ├── Deactivate User  → admin/deactivate_handler.php (POST)
    │       └── Delete User      → admin/delete_user_handler.php (POST)
    │
    └── Logout                   → authentication/logout.php
```

---

## Page Descriptions

### Public Pages

| Page | File | Access |
|------|------|--------|
| Login | `authentication/login.php` | Public |
| Register | `authentication/register.php` | Public |
| Forgot Password | `authentication/forgot_password.php` | Public (future) |

### Student Pages

| Page | File | Access |
|------|------|--------|
| Dashboard | `dashboard/index.php` | Student only |
| Profile | `dashboard/profile.php` | Student only |
| Exercise — List | `modules/exercise/index.php` | Student only |
| Exercise — Add | `modules/exercise/add.php` | Student only |
| Exercise — Edit | `modules/exercise/edit.php` | Student only |
| Diary — List | `modules/diary/index.php` | Student only |
| Diary — Add | `modules/diary/add.php` | Student only |
| Diary — View | `modules/diary/view.php` | Student only |
| Diary — Edit | `modules/diary/edit.php` | Student only |
| Money — List | `modules/money/index.php` | Student only |
| Money — Add | `modules/money/add.php` | Student only |
| Money — Edit | `modules/money/edit.php` | Student only |
| Habit — List | `modules/habit/index.php` | Student only |
| Habit — Add | `modules/habit/add.php` | Student only |
| Habit — Edit | `modules/habit/edit.php` | Student only |

### Admin Pages

| Page | File | Access |
|------|------|--------|
| Admin Dashboard | `admin/index.php` | Admin only |
| User List | `admin/users.php` | Admin only |
| User Detail | `admin/user_detail.php` | Admin only |

---

## Navigation Flow

### Student Navigation Flow

1. **Unauthenticated access** → Redirected to `login.php` by `auth_guard.php`.
2. **Successful login** → Lands on `dashboard/index.php`.
3. **Dashboard** displays quick links/cards to all four modules.
4. **Each module link** takes the student to that module's index (list view).
5. **From any module index** the student can:
   - Click "Add" to go to the add form.
   - Click "Edit" next to a record to go to the edit form.
   - Click "Delete" to trigger a delete confirmation and handler.
6. **Profile link** in the navigation bar takes the student to `dashboard/profile.php`.
7. **Logout link** in the navigation bar hits `logout.php`, destroys session, returns to login.

### Admin Navigation Flow

1. **Admin login** → After successful authentication with role="admin", lands on `admin/index.php`.
2. **Admin Dashboard** displays user count and recent system statistics.
3. **"Manage Users"** link takes admin to `admin/users.php` (full user list).
4. **Clicking a user** displays `admin/user_detail.php` with that student's profile summary.
5. From the user detail page, admin can trigger Deactivate or Delete actions.
6. **Logout** returns admin to login page.

### Access Guard Enforcement

- Every student page begins with `require_once 'includes/auth_guard.php'`.
- Every admin page begins with `require_once 'includes/admin_guard.php'`.
- Any unauthenticated or wrong-role access is immediately redirected — there are no pages accessible without a valid session.

---

*Document prepared for: Student Routine Organizer — University Group Assignment*
