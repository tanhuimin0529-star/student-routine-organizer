# Exercise Tracker Module — Documentation
### Student Routine Organizer (Group Project)
### Module Owner: Exercise Tracker

---

## 1. Functional Requirements

| # | Feature | Description |
|---|---------|--------------|
| 1 | Add Exercise | Logged-in user can log a new workout with activity type, duration, calories, date and notes. |
| 2 | View Exercise Records | User sees a table of only their own records, newest first. |
| 3 | Exercise Details | User can open one record and see every field. |
| 4 | Edit Exercise | User can update any field of their own record, with re-validation. |
| 5 | Delete Exercise | User can delete their own record after a confirmation prompt. |
| 6 | Search / Filter / Sort | User can search by activity, filter by date, and sort by newest, oldest, highest calories, or longest duration. |
| 7 | Statistics | User sees total workouts, total calories, total duration, most frequent activity and this month's workout count. |
| 8 | Session Protection | Only logged-in users can reach any Exercise Tracker page. |
| 9 | Cookies | The app remembers the user's last selected activity type and preferred sort order. |

For each feature:

- **Input:** form fields entered by the student (activity type, duration, calories, date, notes) or GET parameters (search, filter, sort).
- **Process:** PHP validates the input, then a prepared `mysqli` statement reads/writes the `exercise` table, always scoped to `user_id = $_SESSION['user_id']`.
- **Output:** either an HTML page (list, details, form) or a redirect back to `exercise_list.php` with a success message.
- **Validation:** required fields cannot be empty, duration must be a positive number, calories cannot be negative, and the date must be a real calendar date. Errors are collected and shown at the top of the form; entered values are kept so the student does not have to retype everything.

---

## 2. Database Design

**Users** (assumed to already exist, created by the Authentication module)

| Column | Type | Notes |
|---|---|---|
| user_id | INT, PK, AUTO_INCREMENT | |
| name | VARCHAR(100) | |
| email | VARCHAR(100) | UNIQUE |
| password | VARCHAR(255) | |
| role | ENUM('student','admin') | |
| created_at | TIMESTAMP | |

**Exercise**

| Column | Type | Notes |
|---|---|---|
| exercise_id | INT, PK, AUTO_INCREMENT | |
| user_id | INT | FK → users(user_id), ON DELETE CASCADE |
| activity_type | VARCHAR(50) | e.g. Jogging, Gym, Yoga |
| duration | INT | minutes |
| calories_burned | INT | |
| exercise_date | DATE | |
| notes | VARCHAR(255) | optional |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | auto-updates on edit |

Full CREATE TABLE statements, foreign keys and sample INSERT data are in `database/exercise_schema.sql`.

---

## 3. System Architecture (Three-Tier)

```
 ┌────────────────────────┐
 │   Presentation Layer    │  exercise_list.php, add_exercise.php,
 │   (HTML + PHP output)   │  edit_exercise.php, exercise_details.php,
 └───────────┬─────────────┘  delete_exercise.php, exercise.css
             │
 ┌───────────▼─────────────┐
 │   Business Logic Layer   │  exercise_functions.php
 │  (validation + rules)    │  session_check.php
 └───────────┬─────────────┘
             │
 ┌───────────▼─────────────┐
 │   Database Layer         │  config/database.php (mysqli connection)
 │  (MySQL via mysqli)      │  exercise_schema.sql
 └──────────────────────────┘
```

---

## 4. Flowchart — Add Exercise

```
Start
  │
  ▼
User opens Add Exercise page
  │
  ▼
User fills form and submits
  │
  ▼
Are all fields valid? ──No──► Show error messages, keep entered values
  │
 Yes
  │
  ▼
Insert record with logged-in user's ID (prepared statement)
  │
  ▼
Save "last activity" cookie
  │
  ▼
Redirect to Exercise List with success message
  │
  ▼
End
```

---

## 5. Use Case Diagram (description)

**Actor: Student**
- Add Exercise
- View My Exercise Records
- View Exercise Details
- Edit My Exercise
- Delete My Exercise
- Search / Filter / Sort Records

**Actor: Admin**
- View all users' exercise summaries (via the Admin module, outside this module's file scope)

All student use cases include the "Login" use case as a precondition (session check).

---

## 6. Sequence Diagram — View Exercise List (description)

```
Student → Browser: opens exercise_list.php
Browser → session_check.php: checks session
session_check.php → Browser: OK, user_id available
Browser → exercise_functions.php: getExercisesForUser(user_id, ...)
exercise_functions.php → MySQL: SELECT ... WHERE user_id = ?
MySQL → exercise_functions.php: rows
exercise_functions.php → Browser: array of exercises
Browser → Student: renders HTML table
```

---

## 7. ER Diagram (description)

```
USERS (1) ────────< (many) EXERCISE
  user_id PK                exercise_id PK
                             user_id FK → users.user_id
```

One user can have many exercise records; each exercise record belongs to exactly one user.

---

## 8. Data Dictionary

| Table | Field | Type | Description |
|---|---|---|---|
| exercise | exercise_id | INT | Unique id for each workout record |
| exercise | user_id | INT | Owner of the record (FK to users) |
| exercise | activity_type | VARCHAR(50) | Type of exercise performed |
| exercise | duration | INT | Length of the workout in minutes |
| exercise | calories_burned | INT | Estimated calories burned |
| exercise | exercise_date | DATE | Date the workout happened |
| exercise | notes | VARCHAR(255) | Optional free-text notes |
| exercise | created_at | TIMESTAMP | When the record was first added |
| exercise | updated_at | TIMESTAMP | When the record was last edited |

---

## 9. Module Description

The Exercise Tracker Module lets a logged-in student record their workouts and see their exercise history at a glance. It plugs into the shared `users` table and session system used across the whole Student Routine Organizer, so it never needs to manage its own logins — it only checks that `$_SESSION['user_id']` is set before doing anything. Every database query filters or updates rows using that user id, which is what stops one student from ever seeing or changing another student's data.

---

## 10. User Manual

1. **Login** to the Student Routine Organizer using your student account.
2. From the **Dashboard**, click the **Exercise Tracker** button.
3. You will land on the **Exercise List** page, showing your past workouts (if any) and your statistics.
4. Click **+ Add New Exercise** to log a new workout. Fill in the activity, duration, calories, date, and optional notes, then click **Save Exercise**.
5. On the list page, use the **search box**, **date filter**, and **sort dropdown** to find specific records.
6. Click **View** next to any record to see its full details.
7. Click **Edit** to change a record's details, or **Delete** to remove it (you will be asked to confirm before it is deleted).
8. Click **Logout** in the navigation bar when finished.

---

## 11. Screenshots to Capture (for the assignment report)

1. Login page → after logging in, before opening the tracker.
2. Dashboard page showing the Exercise Tracker button.
3. Exercise List page with a few sample records and the statistics cards.
4. Add Exercise form, empty.
5. Add Exercise form showing a validation error (e.g. empty duration).
6. Exercise List after a successful "Exercise added successfully" message.
7. Exercise Details page for one record.
8. Edit Exercise form pre-filled with existing data.
9. Delete confirmation popup (`confirm()` dialog) before deleting.
10. phpMyAdmin view of the `exercise` table with sample data.

---

## 12. Testing Scenarios

| # | Test Case | Steps | Expected Result |
|---|-----------|-------|------------------|
| 1 | Login successfully | Enter correct email & password | Redirected to Dashboard |
| 2 | Access module while logged out | Visit `exercise_list.php` directly without logging in | Redirected to `login.php` |
| 3 | Add Exercise (valid data) | Fill all fields correctly, submit | Record saved, "Exercise added successfully" shown |
| 4 | Add Exercise (empty fields) | Leave duration blank, submit | Error: "Duration must be a positive number." |
| 5 | Add Exercise (negative calories) | Enter -50 calories, submit | Error: "Calories burned cannot be negative." |
| 6 | View Exercise Records | Open Exercise List | Only the logged-in user's records appear, newest first |
| 7 | View another user's record by URL | Manually change `?id=` to another user's exercise_id | Redirected back to Exercise List (access denied) |
| 8 | View Exercise Details | Click "View" on a record | All fields for that record are displayed |
| 9 | Edit Exercise | Change duration and save | "Exercise updated successfully" and new value shown in list |
| 10 | Delete Exercise | Click "Delete", confirm the popup | Record removed, "Exercise deleted successfully" shown |
| 11 | Cancel Delete | Click "Delete", press Cancel on the popup | Record is NOT deleted |
| 12 | Search by activity | Type "Yoga" in search box | Only Yoga records shown |
| 13 | Filter by date | Pick a specific date | Only records from that date shown |
| 14 | Sort by highest calories | Choose "Highest Calories" | Records ordered from highest to lowest calories |
| 15 | Cookie - last activity | Add a "Cycling" record, reopen Add Exercise form | "Cycling" is pre-selected in the dropdown |
| 16 | Cookie - sort preference | Change sort to "Oldest First", reopen Exercise List later | List still sorted oldest first |
| 17 | SQL Injection attempt | Enter `' OR '1'='1` in the search box | No error, treated as plain text, no extra records shown |
| 18 | XSS attempt | Enter `<script>alert(1)</script>` in notes | Displayed as harmless text, script does not run |

---

## 13. Notes on Security

- All database reads/writes for exercise data use **mysqli prepared statements** with bound parameters, which prevents SQL Injection.
- All output printed back into HTML uses **`htmlspecialchars()`**, which prevents Cross-Site Scripting (XSS).
- All text input is **`trim()`**-ed before validation and storage.
- Database connection errors are caught and shown as a friendly message; the real MySQL error is never shown to the user.
- Every exercise page starts with the session guard (`includes/session_check.php`), and every query includes `user_id = ?` so students can only ever see or modify their own records.
