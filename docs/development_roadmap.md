# Section 2 — Development Roadmap

---

## Overview

The project is divided into **10 phases** progressing from planning through deployment. Each phase builds on the previous one. Phases 4–7 (the four feature modules) can be developed in parallel by different team members once Phase 3 (Authentication) is complete.

---

## Phase 1 — Planning

**Duration:** Week 1

### Goals
- Define project scope, objectives, and system requirements.
- Assign roles and responsibilities to all four team members.
- Establish the GitHub repository with the agreed folder structure.
- Agree on coding standards, naming conventions, and Git workflow.

### Deliverables
- Completed project overview document.
- Finalized folder structure committed to the repository.
- Development roadmap committed to `docs/`.
- Work distribution document.
- Git branch strategy agreed and initial branches created.

### Dependencies
- None. This is the starting phase.

---

## Phase 2 — Database Design

**Duration:** Week 1 (overlapping with Phase 1)

### Goals
- Design the complete relational database schema.
- Identify all tables, columns, primary keys, foreign keys, and relationships.
- Create the ER Diagram document.
- Prepare the database schema definition file (non-SQL, text-based description).

### Deliverables
- Database planning document (Section 6 of this plan).
- ER Diagram description document.
- Named database schema ready for implementation.
- Database file structure prepared in `database/` folder.

### Dependencies
- Phase 1 must be complete (scope and module list confirmed).

---

## Phase 3 — Authentication System

**Duration:** Week 1–2

### Goals
- Implement the user registration and login system.
- Implement session management and logout.
- Implement role-based access control (Student vs Admin).
- Implement password hashing and security strategies.
- Implement cookie handling for persistent login (optional).

### Deliverables
- Authentication folder structure complete with all planned files.
- Registration, login, logout flows functional.
- Session and role guard helpers implemented.
- Authentication tested and verified.

### Dependencies
- Phase 2 must be complete (users table designed).
- Database connection configuration file must be ready.

---

## Phase 4 — Exercise Tracker Module

**Duration:** Week 2

### Goals
- Implement all CRUD operations for exercise logging.
- Connect the module to the authenticated user session.
- Implement input validation (client-side and server-side).
- Integrate the module into the student dashboard.

### Deliverables
- Exercise module folder complete with all planned files.
- Add, view, edit, delete exercise records functional.
- Module protected by session guard.
- Module tested with sample data.

### Dependencies
- Phase 3 (Authentication) must be complete.
- Phase 2 (exercise_logs table designed) must be complete.

---

## Phase 5 — Diary Journal Module

**Duration:** Week 2

### Goals
- Implement all CRUD operations for diary entries.
- Connect the module to the authenticated user session.
- Implement mood tagging and privacy controls.
- Integrate the module into the student dashboard.

### Deliverables
- Diary module folder complete with all planned files.
- Add, view, edit, delete diary entries functional.
- Module protected by session guard.
- Module tested with sample data.

### Dependencies
- Phase 3 (Authentication) must be complete.
- Phase 2 (diary_entries table designed) must be complete.

---

## Phase 6 — Money Tracker Module

**Duration:** Week 2–3

### Goals
- Implement all CRUD operations for financial records (income and expenses).
- Connect the module to the authenticated user session.
- Implement category-based filtering.
- Display a basic balance summary.
- Integrate the module into the student dashboard.

### Deliverables
- Money module folder complete with all planned files.
- Add, view, edit, delete financial records functional.
- Balance calculation display working.
- Module protected by session guard.
- Module tested with sample data.

### Dependencies
- Phase 3 (Authentication) must be complete.
- Phase 2 (money_records table designed) must be complete.

---

## Phase 7 — Habit Tracker Module

**Duration:** Week 2–3

### Goals
- Implement habit creation and daily check-in functionality.
- Implement streak tracking for each habit.
- Connect the module to the authenticated user session.
- Integrate the module into the student dashboard.

### Deliverables
- Habit module folder complete with all planned files.
- Create habits, mark daily completion, view progress functional.
- Streak logic implemented.
- Module protected by session guard.
- Module tested with sample data.

### Dependencies
- Phase 3 (Authentication) must be complete.
- Phase 2 (habits and habit_logs tables designed) must be complete.

---

## Phase 8 — Admin Dashboard

**Duration:** Week 3

### Goals
- Implement the Admin Dashboard accessible only to admin-role users.
- Display a list of all registered users.
- Implement user deactivation and deletion functionality.
- Display summary statistics of system usage.

### Deliverables
- Admin folder complete with all planned files.
- Admin login gate functional (role guard redirects non-admins).
- User management features implemented.
- Admin dashboard tested.

### Dependencies
- Phase 3 (Authentication and role-based access) must be complete.
- Phases 4–7 partially complete (to show usage summaries).

---

## Phase 9 — Testing

**Duration:** Week 3–4

### Goals
- Conduct systematic testing of all modules and features.
- Perform authentication and session security testing.
- Perform CRUD operation testing for all four modules.
- Perform cross-module integration testing.
- Conduct User Acceptance Testing (UAT) with simulated student scenarios.

### Deliverables
- Testing Plan document (Section 14 of this plan).
- Test case records for each module.
- Bug report and resolution log.
- Final tested build committed to `main` branch.

### Dependencies
- Phases 3–8 must all be functionally complete.

---

## Phase 10 — Deployment & Documentation Finalization

**Duration:** Week 4

### Goals
- Finalize all documentation in the `docs/` folder.
- Prepare the installation guide for local XAMPP setup.
- Complete the project report draft.
- Confirm all files are committed and the `main` branch is clean.
- Prepare for university submission.

### Deliverables
- `docs/` folder fully populated.
- Final `README.md` complete and polished.
- Project report draft complete.
- All branches merged to `main`.
- Repository tagged with a release version (e.g., `v1.0.0`).

### Dependencies
- Phase 9 (Testing) must be complete.
- All documentation sections must be finalized.

---

## Roadmap Summary Table

| Phase | Name | Duration | Depends On |
|-------|------|----------|------------|
| 1 | Planning | Week 1 | — |
| 2 | Database Design | Week 1 | Phase 1 |
| 3 | Authentication | Week 1–2 | Phase 2 |
| 4 | Exercise Module | Week 2 | Phase 3 |
| 5 | Diary Module | Week 2 | Phase 3 |
| 6 | Money Module | Week 2–3 | Phase 3 |
| 7 | Habit Module | Week 2–3 | Phase 3 |
| 8 | Admin Dashboard | Week 3 | Phase 3, 4–7 |
| 9 | Testing | Week 3–4 | Phases 4–8 |
| 10 | Deployment & Docs | Week 4 | Phase 9 |

---

*Document prepared for: Student Routine Organizer — University Group Assignment*
