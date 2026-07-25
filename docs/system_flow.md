# Section 9 — System Flow Planning

---

## Overview

This section describes the workflow for each key operation in the application. Flows are described sequentially from the user action through the PHP Business Logic Layer to the MySQL Database and back to the browser.

---

## Flow 1: User Registration

1. User opens the Registration page.
2. User fills in: Full Name, Email, Password, Confirm Password.
3. Client-side JavaScript validates fields in real time (format, match, length).
4. User clicks "Register".
5. Form is submitted via HTTP POST to `register_handler.php`.
6. Server checks: email uniqueness, field lengths, password match.
7. If validation fails → Return to form with field-specific error messages.
8. If validation passes → Password is hashed using `password_hash()`.
9. A new row is inserted into the `users` table.
10. User is redirected to Login page with a success flash message.

---

## Flow 2: User Login

1. User opens the Login page.
2. User enters Email and Password, optionally checks "Remember Me".
3. Client-side validates that fields are not empty.
4. Form submitted via POST to `login_handler.php`.
5. PHP queries `users` table for a record matching the submitted email.
6. If no match found → Return to login with a generic error message.
7. If match found → `password_verify()` compares submitted password to stored hash.
8. If verification fails → Return to login with the same generic error message.
9. If verification succeeds:
   - Session is started.
   - Session ID is regenerated (prevents session fixation).
   - User data (ID, name, email, role) is stored in `$_SESSION`.
   - If "Remember Me" is checked, a secure cookie token is created.
10. Role checked: student → Dashboard. Admin → Admin Dashboard.

---

## Flow 3: User Logout

1. User clicks "Logout" link from any page.
2. Request goes to `logout.php`.
3. PHP retrieves and destroys the current session (`session_destroy()`).
4. "Remember Me" cookie is cleared (if set).
5. Any database-stored cookie token is deleted.
6. User is redirected to Login page with a "You have been logged out" message.

---

## Flow 4: Exercise Tracker — Add Record

1. Student opens Dashboard and clicks "Exercise Tracker".
2. Exercise index page loads, showing the list of their past records.
3. Student clicks "Add Exercise".
4. Add form loads with all required fields.
5. Student fills in: Type, Duration, Intensity, Date, Calories (optional), Notes (optional).
6. Client-side JavaScript validates required fields on submit.
7. Form submitted via POST to `add_handler.php`.
8. PHP sanitizes inputs and validates server-side.
9. If validation fails → Return to add form with error messages.
10. If validation passes → Insert new row into `exercise_logs` with `user_id` from session.
11. Redirect to exercise index with "Exercise record added successfully" flash message.

---

## Flow 5: Exercise Tracker — Edit Record

1. Student views their exercise list.
2. Student clicks "Edit" next to a specific record.
3. Edit form loads pre-populated with current record values.
4. `edit.php` verifies that the `log_id` from the URL belongs to the current `user_id` (ownership check).
5. Student modifies fields and submits.
6. Form submitted via POST to `edit_handler.php`.
7. PHP re-validates all fields server-side.
8. Ownership is verified again in the handler before update.
9. If validation passes → `UPDATE` the row in `exercise_logs`.
10. Redirect to exercise index with a success message.

---

## Flow 6: Exercise Tracker — Delete Record

1. Student views exercise list.
2. Student clicks "Delete" next to a record.
3. A confirmation prompt is shown (JavaScript `confirm()` dialog or a dedicated confirmation page).
4. On confirmation, a POST request is sent to `delete_handler.php` with `log_id`.
5. PHP verifies that the `log_id` belongs to the current `user_id`.
6. If valid → Execute `DELETE` on `exercise_logs` where `log_id = ? AND user_id = ?`.
7. Redirect to exercise index with a "Record deleted" message.

*(The same Add/Edit/Delete flow pattern applies identically to Diary, Money, and Habit modules with their respective fields and tables.)*

---

## Flow 7: Diary Journal — View Entry

1. Student opens Diary index page (list view of all entries).
2. Student clicks the title of an entry.
3. `view.php` is loaded with `entry_id` passed via GET parameter.
4. PHP verifies `entry_id` belongs to the session `user_id`.
5. Full entry content, mood, date, and title are retrieved from `diary_entries` and displayed.
6. Student sees "Edit" and "Delete" action buttons on the view page.

---

## Flow 8: Money Tracker — Balance Calculation

1. Student opens the Money Tracker index.
2. PHP queries `money_records` filtered by `user_id`.
3. PHP calculates:
   - **Total Income** = Sum of all `amount` where `transaction_type = 'Income'`.
   - **Total Expenses** = Sum of all `amount` where `transaction_type = 'Expense'`.
   - **Balance** = Total Income − Total Expenses.
4. Summary totals are displayed at the top of the page.
5. Transaction list is displayed below the summary in descending date order.

---

## Flow 9: Habit Tracker — Daily Check-In

1. Student opens Habit Tracker index.
2. Page displays all active habits with today's check-in status.
3. For each habit: if not yet checked in today, a "Mark Done" button is shown.
4. Student clicks "Mark Done" for a habit.
5. A POST request is sent to `checkin_handler.php` with `habit_id`.
6. PHP checks that today's date has not already been logged for this habit (unique constraint enforcement).
7. If no duplicate → Insert a new row into `habit_logs`.
8. PHP recalculates the `current_streak` for the habit:
   - Checks consecutive days in `habit_logs` going backwards from today.
   - Updates `habits.current_streak`.
9. Page refreshes; the habit now shows "Completed Today ✓" and the updated streak count.

---

## Flow 10: Admin — User Management

1. Admin logs in and lands on the Admin Dashboard.
2. Admin clicks "Manage Users".
3. `admin/users.php` queries all rows from `users` table.
4. Admin sees a list of all registered users with name, email, role, and status.
5. Admin clicks on a specific user → `user_detail.php` loads that user's summary.
6. Admin clicks "Deactivate":
   - POST request sent to `deactivate_handler.php`.
   - PHP updates `users.is_active = 0` for the given `user_id`.
   - User will be blocked from logging in on their next attempt.
7. Admin clicks "Delete User":
   - POST request sent to `delete_user_handler.php`.
   - PHP executes `DELETE FROM users WHERE user_id = ?`.
   - All related records in all module tables are deleted via CASCADE.
   - Redirect to user list with confirmation message.

---

*Document prepared for: Student Routine Organizer — University Group Assignment*
