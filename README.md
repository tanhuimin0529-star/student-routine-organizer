# Student Routine Organizer — Exercise Tracker Module

This package contains **only the Exercise Tracker module**, as required. It is built to slot into the larger Student Routine Organizer project (Diary Journal, Money Tracker, Habit Tracker, Authentication, Dashboard, Admin are separate modules built by teammates).

## Setup (XAMPP + phpMyAdmin)

1. Copy this whole folder into `C:/xampp/htdocs/student-routine-organizer/` (or your XAMPP `htdocs` path).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
4. Go to the **Import** tab and import `database/exercise_schema.sql`. This creates the database, the `users` table, the `exercise` table, and some sample data.
5. Visit `http://localhost/student-routine-organizer/authentication/hash_seed_passwords.php` **once** — this converts the sample plain-text passwords into proper hashed passwords. Delete this file afterwards.
6. Go to `http://localhost/student-routine-organizer/authentication/login.php` and log in with a sample account: `ali@example.com` / `password123`.
7. You'll land on the Dashboard, where you can click into the Exercise Tracker.

You can also register a brand-new account at `authentication/register.php`.

## Folder Structure

```
student-routine-organizer/
├── database/exercise_schema.sql      ← SQL: create DB, tables, sample data
├── config/database.php               ← mysqli connection (Database Layer, shared by all modules)
├── includes/
│   ├── session_check.php             ← Login/session guard (used by Exercise Tracker pages)
│   ├── header.php                    ← Shared page top + navigation
│   └── footer.php                    ← Shared page bottom
├── assets/css/
│   ├── exercise.css                  ← Styling for Exercise Tracker + Dashboard
│   └── auth.css                      ← Styling for Login / Register pages
├── authentication/
│   ├── auth_functions.php            ← Validation + DB functions (Business Logic Layer)
│   ├── register.php                  ← Create a new student account
│   ├── login.php                     ← Login, starts the session
│   ├── logout.php                    ← Destroys the session
│   └── hash_seed_passwords.php       ← Run ONCE after importing the SQL, then delete
├── dashboard/
│   └── dashboard.php                 ← Minimal landing page after login (placeholder for the real Dashboard module)
├── modules/exercise/
│   ├── exercise_functions.php        ← Validation + DB functions (Business Logic Layer)
│   ├── exercise_list.php             ← View / search / filter / sort / statistics
│   ├── add_exercise.php              ← Add a new record
│   ├── exercise_details.php          ← View one record
│   ├── edit_exercise.php             ← Update a record
│   └── delete_exercise.php           ← Delete a record (after JS confirm)
└── docs/
    └── exercise_module_documentation.md
```

## Notes

- Written in plain **procedural PHP** with **mysqli** (no frameworks, no Composer).
- Passwords are hashed with `password_hash()` / checked with `password_verify()` — never stored as plain text for real accounts. Only the SQL seed data starts as plain text, which is why `hash_seed_passwords.php` exists (see setup step 5).
- `dashboard/dashboard.php` is a minimal placeholder so login has somewhere to go and the Exercise Tracker button has a home — swap it out for your teammate's real Dashboard module whenever it's ready (just keep the Exercise Tracker link).
- Diary Journal, Money Tracker, Habit Tracker, and Admin are still separate modules built by your teammates — not included here.
- See `docs/exercise_module_documentation.md` for full Exercise Tracker documentation: functional requirements, ER diagram, flowchart, use case diagram, sequence diagram, data dictionary, user manual, and testing scenarios.
