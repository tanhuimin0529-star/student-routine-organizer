# Student Routine Organizer

> A web-based daily routine management system for university students, built with PHP, MySQL, and XAMPP following a Three-Tier Architecture.

---

## Project Description

The **Student Routine Organizer** is a collaborative university group project that provides students with a centralized platform to manage four core aspects of their daily academic and personal lives:

- 🏃 Physical activity via the **Exercise Tracker**
- 📖 Mental well-being via the **Diary Journal**
- 💰 Financial awareness via the **Money Tracker**
- ✅ Habit formation via the **Habit Tracker**

The system includes secure user authentication with role-based access control and an Administrator Dashboard for user management. It is designed for local deployment on XAMPP and developed collaboratively by four students using GitHub.

---

## Features

- ✅ Secure registration, login, and logout with session management
- ✅ Role-based access control (Student / Admin)
- ✅ Password hashing using industry-standard bcrypt algorithm
- ✅ Full CRUD operations across all four modules
- ✅ Admin dashboard with user deactivation and deletion
- ✅ Clean Three-Tier Architecture (Presentation / Business Logic / Data)
- ✅ Input validation both client-side (JavaScript) and server-side (PHP)
- ✅ Session-based flash messaging system
- ✅ Modular codebase — each feature is isolated in its own directory

---

## Modules

| Module | Description |
|--------|-------------|
| **Exercise Tracker** | Log workout sessions with type, duration, intensity, date, and calories |
| **Diary Journal** | Write personal journal entries with mood tagging and privacy controls |
| **Money Tracker** | Record income and expenses by category with automatic balance calculation |
| **Habit Tracker** | Define habits, check in daily, and track streaks |

---

## Folder Structure

```
student-routine-organizer/
├── README.md
├── LICENSE
├── .gitignore
├── docs/                    ← All project documentation
├── database/                ← DB schema and seed files
├── config/                  ← Database config (not committed)
├── assets/
│   ├── css/                 ← Stylesheets per page/module
│   ├── js/                  ← JavaScript per module
│   └── images/
├── includes/                ← Shared PHP: DB, session, guards, header, footer
├── authentication/          ← Registration, login, logout
├── dashboard/               ← Student dashboard and profile
├── modules/
│   ├── exercise/            ← Exercise Tracker CRUD
│   ├── diary/               ← Diary Journal CRUD
│   ├── money/               ← Money Tracker CRUD
│   └── habit/               ← Habit Tracker CRUD + check-in
└── admin/                   ← Admin dashboard and user management
```

---

## Installation Overview

> Full setup instructions are available in `docs/project_overview.md`.

1. Install XAMPP and ensure Apache and MySQL services are running.
2. Clone this repository into the `htdocs` folder of your XAMPP installation.
3. Create the database in phpMyAdmin using the schema file in `database/`.
4. Copy `config/db_config.example.php` to `config/db_config.php` and update it with your local database credentials.
5. Navigate to `http://localhost/student-routine-organizer` in your browser.
6. Register a student account or use the seeded admin account provided in `database/seed_data.sql`.

---

## Development Workflow

This project follows the **Feature Branch Workflow**:

1. Pick up your assigned feature from the GitHub Project Board.
2. Create your branch from `develop`: `feature/your-module`
3. Commit changes using the convention: `[type]: [short description]`
4. Push your branch and open a Pull Request to `develop`.
5. Another team member reviews and approves your PR.
6. After approval, merge into `develop`.
7. The Team Lead merges stable `develop` into `main` at each milestone.

See `docs/git_branch_strategy.md` for full details.

---

## Team Members

| Name | Role | Module Owned |
|------|------|-------------|
| Member 1 | Team Lead & Auth Developer | Authentication, Dashboard, Shared Layer |
| Member 2 | Exercise Module Developer | Exercise Tracker |
| Member 3 | Diary & Money Developer | Diary Journal, Money Tracker |
| Member 4 | Habit & Admin Developer | Habit Tracker, Admin Dashboard |

---

## Contribution Guidelines

1. **Never commit directly to `main` or `develop`.**
2. All changes must go through a Pull Request with at least one reviewer.
3. Follow the commit message convention (see `docs/git_branch_strategy.md`).
4. Check the GitHub Project Board for available tasks and assignments.
5. Document your work in the relevant `docs/` files as you go.
6. Do not commit `config/db_config.php` — it is listed in `.gitignore`.
7. Ensure all forms have both client-side and server-side validation before merging.

---

## Project Status

| Phase | Status |
|-------|--------|
| Planning & Documentation | ✅ Complete |
| Database Design | ✅ Complete |
| Authentication | 🔄 In Progress |
| Exercise Module | 🔄 In Progress |
| Diary Module | ⬜ Pending |
| Money Module | ⬜ Pending |
| Habit Module | ⬜ Pending |
| Admin Dashboard | ⬜ Pending |
| Testing | ⬜ Pending |
| Deployment | ⬜ Pending |

---

## License

This project is licensed under the **MIT License**. See the [LICENSE](LICENSE) file for details.

---

## Acknowledgements

- University assignment guidelines and requirements.
- XAMPP by Apache Friends for local PHP/MySQL development.
- PHP documentation at [php.net](https://www.php.net).
- MySQL Reference Manual at [dev.mysql.com](https://dev.mysql.com/doc/).

---

*Student Routine Organizer — University Group Assignment | PHP + MySQL + XAMPP | Three-Tier Architecture*
