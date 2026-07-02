# Section 1 — Project Overview

---

## Project Name
**Student Routine Organizer**

---

## Project Description

The Student Routine Organizer is a web-based application developed using PHP, MySQL, HTML, CSS, and JavaScript following a **Three-Tier Architecture**. It is designed to help university students manage their daily academic and personal routines through four integrated modules: Exercise Tracker, Diary Journal, Money Tracker, and Habit Tracker. The system is hosted locally using XAMPP and will be collaboratively developed by a team of four university students via GitHub.

---

## Problem Statement

University students frequently struggle to manage multiple aspects of their daily lives — physical health, mental well-being, finances, and personal habits — without a unified tool. Existing solutions are often fragmented across multiple apps, are commercial in nature, or lack features tailored to student needs. This project addresses that gap by delivering a single, lightweight, locally-hostable routine organizer purpose-built for students.

---

## Objectives

1. Provide students with a centralized platform to plan and track daily routines.
2. Implement a secure authentication system with role-based access control.
3. Enable full CRUD operations across all four core modules.
4. Apply the Three-Tier Architecture to maintain a clean separation between the Presentation, Business Logic, and Data layers.
5. Practice collaborative software development using Git and GitHub workflows.
6. Produce professional documentation suitable for university submission.

---

## Target Users

| User Type | Description |
|-----------|-------------|
| **Students** | Primary users who register, log in, and manage their personal routines. |
| **Administrators** | System managers who oversee registered users, system health, and content moderation. |

---

## User Roles

### Student (Standard User)
- Self-register and manage their own account.
- Access their personal dashboard.
- Perform full CRUD on their own Exercise, Diary, Money, and Habit records.
- Cannot access other students' data.

### Administrator (Admin)
- Access the Admin Dashboard.
- View all registered users and their activity summaries.
- Deactivate or delete user accounts.
- Cannot modify individual student records unless flagged.

---

## Scope

### In Scope
- User registration, login, logout, and session management.
- Exercise Tracker module with logging of workout sessions.
- Diary Journal module for personal journaling.
- Money Tracker module for income and expense recording.
- Habit Tracker module for daily habit monitoring.
- Admin dashboard for user management.
- Role-based access control (Student vs Admin).
- Basic client-side and server-side input validation.
- Local deployment via XAMPP.

### Out of Scope
- Mobile application development.
- Email notifications or reminders.
- Social/sharing features between students.
- Production server deployment (cloud hosting).
- Third-party API integrations.
- Reporting/analytics beyond basic summaries.

---

## Technologies

| Layer | Technology |
|-------|------------|
| **Presentation Layer** | HTML5, CSS3, JavaScript (optional enhancements) |
| **Business Logic Layer** | PHP 8.x |
| **Data Layer** | MySQL 8.x |
| **Local Server** | XAMPP (Apache + MySQL) |
| **Version Control** | Git + GitHub |
| **IDE** | VS Code / PHPStorm (team preference) |

---

## System Architecture Overview

The project adopts a **Three-Tier Architecture**:

### Tier 1 — Presentation Layer (Client Side)
- All `.html` and `.php` view files rendered in the browser.
- CSS stylesheets for layout and visual design.
- JavaScript for client-side form validation and dynamic UI behavior.
- Located in: `dashboard/`, `modules/`, `authentication/`, `admin/` directories.

### Tier 2 — Business Logic Layer (Server Side)
- PHP scripts that handle requests from the Presentation Layer.
- Enforce business rules, perform input validation, manage sessions, and coordinate data flow.
- Located in: `includes/`, and within module subdirectories as controller/handler PHP files.

### Tier 3 — Data Layer (Database)
- MySQL database storing all persistent application data.
- Accessed exclusively through PHP Data Objects (PDO) or MySQLi within server-side scripts.
- Schema and seed data files located in: `database/` directory.

### Architecture Data Flow

```
Browser (HTML/CSS/JS)
       ↓ HTTP Request
PHP Controller / Handler
       ↓ SQL Query (PDO)
MySQL Database
       ↑ Result Set
PHP Controller / Handler
       ↑ HTML Response
Browser (HTML/CSS/JS)
```

---

*Document prepared for: Student Routine Organizer — University Group Assignment*
*Architecture: Three-Tier | Stack: PHP, MySQL, XAMPP, HTML, CSS, JavaScript*
