# Section 14 — Testing Plan

---

## Overview

Testing is conducted throughout the project but formalized in Week 3-4. Every module is tested individually first (unit-level) and then together (integration-level). A final round of User Acceptance Testing simulates real student usage.

---

## 1. Authentication Testing

| Test Case | Description | Expected Result |
|-----------|-------------|-----------------|
| TC-AUTH-01 | Register with valid data | User created, redirect to login with success message |
| TC-AUTH-02 | Register with duplicate email | Error: "Email already registered" |
| TC-AUTH-03 | Register with missing required fields | Error message for each missing field |
| TC-AUTH-04 | Register with password mismatch | Error: "Passwords do not match" |
| TC-AUTH-05 | Register with weak password (<8 chars) | Error: "Password too short" |
| TC-AUTH-06 | Login with correct credentials (student) | Redirect to student dashboard |
| TC-AUTH-07 | Login with correct credentials (admin) | Redirect to admin dashboard |
| TC-AUTH-08 | Login with wrong password | Generic error message (not revealing which field is wrong) |
| TC-AUTH-09 | Login with unregistered email | Generic error message |
| TC-AUTH-10 | Access protected page without session | Redirect to login |
| TC-AUTH-11 | Student accesses admin page | Redirect to student dashboard with access denied |
| TC-AUTH-12 | Logout clears session | Session destroyed, redirect to login |
| TC-AUTH-13 | Session timeout after inactivity | Auto-redirect to login |

---

## 2. CRUD Testing

### Exercise Module

| Test Case | Description | Expected Result |
|-----------|-------------|-----------------|
| TC-EX-01 | Add exercise with valid data | Record saved, listed on index |
| TC-EX-02 | Add exercise with invalid duration (0) | Validation error |
| TC-EX-03 | Add exercise with future date | Validation error |
| TC-EX-04 | Edit own exercise record | Record updated, confirmation message |
| TC-EX-05 | Attempt to edit another user's record | Access denied or redirect |
| TC-EX-06 | Delete own exercise record | Record removed from list |
| TC-EX-07 | Attempt to delete another user's record | Access denied |

### Diary Module

| Test Case | Description | Expected Result |
|-----------|-------------|-----------------|
| TC-DI-01 | Add diary entry with valid data | Entry saved, listed on index |
| TC-DI-02 | Add entry with empty content | Validation error |
| TC-DI-03 | View own diary entry | Full entry content displayed |
| TC-DI-04 | Attempt to view another user's entry | Access denied |
| TC-DI-05 | Edit own diary entry | Entry updated |
| TC-DI-06 | Delete own diary entry | Entry removed |

### Money Module

| Test Case | Description | Expected Result |
|-----------|-------------|-----------------|
| TC-MO-01 | Add income transaction | Record saved, balance increases |
| TC-MO-02 | Add expense transaction | Record saved, balance decreases |
| TC-MO-03 | Add transaction with negative amount | Validation error |
| TC-MO-04 | Balance calculation accuracy | Total Income − Total Expense = Displayed Balance |
| TC-MO-05 | Edit transaction | Record updated, balance recalculated |
| TC-MO-06 | Delete transaction | Record removed, balance updated |

### Habit Module

| Test Case | Description | Expected Result |
|-----------|-------------|-----------------|
| TC-HA-01 | Create a new habit | Habit listed in index |
| TC-HA-02 | Check in on a habit today | Record inserted in habit_logs, streak incremented |
| TC-HA-03 | Attempt to check in twice today | Second check-in rejected gracefully |
| TC-HA-04 | View streak count | Streak reflects consecutive check-ins |
| TC-HA-05 | Edit habit details | Habit updated |
| TC-HA-06 | Delete habit | Habit and all logs cascade-deleted |

---

## 3. Session Testing

| Test Case | Description | Expected Result |
|-----------|-------------|-----------------|
| TC-SE-01 | Session persists across page navigations | User remains logged in |
| TC-SE-02 | Navigating back in browser after logout | Does not restore session, shows login |
| TC-SE-03 | Session variable contains correct role | Role-based redirect works correctly |
| TC-SE-04 | Session ID changes after login | New session ID (prevents fixation attack) |

---

## 4. Database Testing

| Test Case | Description | Expected Result |
|-----------|-------------|-----------------|
| TC-DB-01 | All tables created with correct columns | Schema verified against plan |
| TC-DB-02 | Foreign key constraints enforce integrity | Inserting orphan records rejected |
| TC-DB-03 | ON DELETE CASCADE works for users | Deleting user removes all module records |
| TC-DB-04 | Unique constraint on email | Duplicate emails rejected at db level |
| TC-DB-05 | Unique constraint on habit_logs (habit_id, checkin_date) | Duplicate check-ins rejected at db level |

---

## 5. Integration Testing

| Test Case | Description | Expected Result |
|-----------|-------------|-----------------|
| TC-INT-01 | Student logs in and accesses all four modules | All modules load correctly from dashboard |
| TC-INT-02 | One student's records are not visible to another | Verified by testing with two accounts |
| TC-INT-03 | Admin can see all registered users | Admin user list populated from all accounts |
| TC-INT-04 | Admin deactivation prevents student login | Deactivated account cannot log in |
| TC-INT-05 | Header/footer included on all pages | Navigation consistent across all pages |
| TC-INT-06 | Flash messages display and clear correctly | Confirmation/error messages appear once |

---

## 6. User Acceptance Testing (UAT)

**Scenario 1 — New Student Onboarding:**
A new student registers, logs in, and adds at least one record in each of the four modules. All actions should complete without errors and data should persist after logging out and back in.

**Scenario 2 — Daily Routine Update:**
A returning student logs in, marks their daily habit, logs an exercise session, and checks their money balance. All data should be up to date and accurate.

**Scenario 3 — Admin User Management:**
An admin logs in, views the user list, clicks on a student, deactivates their account, then re-logs as that student and verifies they cannot access the dashboard.

---

*Document prepared for: Student Routine Organizer — University Group Assignment*
